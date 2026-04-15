<?php

namespace App\Services\AI;

use App\DTOs\AIResponseDTO;
use App\Exceptions\AIResponseException;

class AIResponseParser
{
    /**
     * Parse raw AI response string into a typed DTO.
     */
    public function parse(string $rawResponse): AIResponseDTO
    {
        // Try to extract JSON from response (AI might wrap it in markdown code blocks)
        $json = $this->extractJson($rawResponse);

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw AIResponseException::malformed('Invalid JSON: ' . json_last_error_msg());
        }

        if (!is_array($decoded) || !array_key_exists('valid', $decoded)) {
            throw AIResponseException::malformed('Missing "valid" field in AI response.');
        }

        $valid = (bool) $decoded['valid'];

        if (!$valid) {
            return new AIResponseDTO(
                valid: false,
                error: $decoded['error'] ?? 'Unknown validation error from AI.',
            );
        }

        if (empty($decoded['action'])) {
            throw AIResponseException::malformed('Missing "action" field in valid AI response.');
        }

        if (!isset($decoded['data']) || !is_array($decoded['data'])) {
            throw AIResponseException::malformed('Missing or invalid "data" field in AI response.');
        }

        return new AIResponseDTO(
            valid: true,
            action: $decoded['action'],
            data: $decoded['data'],
        );
    }

    /**
     * Extract JSON from a string that might contain markdown code blocks.
     */
    private function extractJson(string $raw): string
    {
        $raw = trim($raw);

        // Strip markdown code fences if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $raw, $matches)) {
            return trim($matches[1]);
        }

        return $raw;
    }
}
