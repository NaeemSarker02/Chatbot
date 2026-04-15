<?php

namespace App\DTOs;

class AIResponseDTO
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $action = null,
        public readonly array $data = [],
        public readonly ?string $error = null,
    ) {}
}
