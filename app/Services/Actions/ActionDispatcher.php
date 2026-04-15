<?php

namespace App\Services\Actions;

use App\DTOs\AIResponseDTO;
use App\DTOs\ActionResultDTO;
use App\Exceptions\InvalidActionException;
use App\Services\Actions\Contracts\ActionHandlerInterface;
use Illuminate\Contracts\Container\Container;

class ActionDispatcher
{
    /**
     * Map of action strings to handler classes.
     */
    private array $handlers = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Register an action string to a handler class.
     */
    public function register(string $action, string $handlerClass): void
    {
        $this->handlers[$action] = $handlerClass;
    }

    /**
     * Dispatch an AI response to the appropriate handler.
     */
    public function dispatch(AIResponseDTO $dto): ActionResultDTO
    {
        $action = $dto->action ?? 'null';

        if (!isset($this->handlers[$action])) {
            throw InvalidActionException::unknown($action);
        }

        /** @var ActionHandlerInterface $handler */
        $handler = $this->container->make($this->handlers[$action]);

        return $handler->execute($dto->data);
    }
}
