<?php

namespace App\Exceptions;

use RuntimeException;

class AIResponseException extends RuntimeException
{
    public static function malformed(string $reason = ''): self
    {
        return new self('AI returned an invalid response.' . ($reason ? " {$reason}" : ''));
    }

    public static function timeout(): self
    {
        return new self('AI API request timed out.');
    }
}
