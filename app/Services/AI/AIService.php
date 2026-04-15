<?php

namespace App\Services\AI;

use App\Exceptions\AIResponseException;
use Illuminate\Support\Facades\Http;

class AIService
{
    public function __construct(
        private readonly SystemPromptBuilder $promptBuilder,
    ) {}

    /**
     * Send user message to the AI API and return the raw response body.
     */
    public function chat(string $userMessage): string
    {
        $url = config('ai.api_url');
        $timeout = config('ai.api_timeout', 30);
        $maxRetries = config('ai.max_retries', 2);

        $systemPrompt = $this->promptBuilder->build();

        $response = Http::timeout($timeout)
            ->retry($maxRetries, 1000)
            ->withoutVerifying()
            ->withToken(config('ai.api_key'))
            ->post($url, [
                'message' => $userMessage,
                'system_prompt' => $systemPrompt,
            ]);

        if ($response->failed()) {
            throw AIResponseException::malformed('API returned HTTP ' . $response->status());
        }

        $body = $response->json();

        if (!isset($body['response'])) {
            throw AIResponseException::malformed('Missing "response" field from AI API.');
        }

        return $body['response'];
    }
}
