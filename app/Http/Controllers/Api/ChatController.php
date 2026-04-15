<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatMessageRequest;
use App\Http\Resources\ChatResponseResource;
use App\Exceptions\AIResponseException;
use App\Exceptions\InvalidActionException;
use App\Models\ChatLog;
use App\Services\AI\AIResponseParser;
use App\Services\AI\AIService;
use App\Services\Actions\ActionDispatcher;

class ChatController extends Controller
{
    public function __construct(
        private readonly AIService $aiService,
        private readonly AIResponseParser $parser,
        private readonly ActionDispatcher $dispatcher,
    ) {}

    public function handle(ChatMessageRequest $request)
    {
        $userMessage = $request->validated('message');
        $rawResponse = null;

        try {
            // 1. Call AI API
            $rawResponse = $this->aiService->chat($userMessage);

            // 2. Parse AI response
            $dto = $this->parser->parse($rawResponse);

            // 3. If AI says invalid, return error
            if (!$dto->valid) {
                $this->log($userMessage, $rawResponse, null, 'invalid', $dto->error);

                return ChatResponseResource::error($dto->error ?? 'AI could not process your message.');
            }

            // 4. Dispatch to the correct action handler
            $result = $this->dispatcher->dispatch($dto);

            // 5. Log the interaction
            $this->log(
                $userMessage,
                $rawResponse,
                $dto->action,
                $result->success ? 'success' : 'failed',
                $result->success ? null : $result->message,
            );

            // 6. Return result
            return ChatResponseResource::fromResult($result);

        } catch (AIResponseException $e) {
            $this->log($userMessage, $rawResponse, null, 'failed', $e->getMessage());

            return ChatResponseResource::error($e->getMessage(), 502);

        } catch (InvalidActionException $e) {
            $this->log($userMessage, $rawResponse, null, 'failed', $e->getMessage());

            return ChatResponseResource::error($e->getMessage(), 422);

        } catch (\Throwable $e) {
            $this->log($userMessage, $rawResponse, null, 'failed', $e->getMessage());

            return ChatResponseResource::error('An unexpected error occurred.', 500);
        }
    }

    private function log(
        string $userMessage,
        ?string $rawResponse,
        ?string $action,
        string $status,
        ?string $error = null,
    ): void {
        ChatLog::create([
            'user_message'     => $userMessage,
            'ai_raw_response'  => $rawResponse,
            'parsed_action'    => $action,
            'status'           => $status,
            'error_message'    => $error,
        ]);
    }
}
