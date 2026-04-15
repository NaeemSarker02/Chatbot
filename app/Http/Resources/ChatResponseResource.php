<?php

namespace App\Http\Resources;

use App\DTOs\ActionResultDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResponseResource extends JsonResource
{
    /**
     * Create from an ActionResultDTO.
     */
    public static function fromResult(ActionResultDTO $result): JsonResponse
    {
        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
            'data'    => $result->data,
            'url'     => $result->resourceUrl,
        ], $result->success ? 200 : 422);
    }

    /**
     * Create an error response.
     */
    public static function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'url'     => null,
        ], $status);
    }

    public function toArray(Request $request): array
    {
        return [];
    }
}
