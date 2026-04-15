<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidActionException extends RuntimeException
{
    public static function unknown(string $action): self
    {
        return new self("Unknown action: {$action}");
    }
}
