<?php

namespace App\Services\Actions\Contracts;

use App\DTOs\ActionResultDTO;

interface ActionHandlerInterface
{
    public function execute(array $data): ActionResultDTO;
}
