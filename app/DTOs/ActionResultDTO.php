<?php

namespace App\DTOs;

class ActionResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $resourceUrl = null,
        public readonly ?array $data = null,
    ) {}
}
