<?php

namespace Tests\Unit;

use App\DTOs\AIResponseDTO;
use App\DTOs\ActionResultDTO;
use App\Exceptions\InvalidActionException;
use App\Services\Actions\ActionDispatcher;
use App\Services\Actions\Contracts\ActionHandlerInterface;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class FakeHandler implements ActionHandlerInterface
{
    public function execute(array $data): ActionResultDTO
    {
        return new ActionResultDTO(
            success: true,
            message: 'Handled.',
            resourceUrl: '/api/customers/1',
            data: $data,
        );
    }
}

class ActionDispatcherTest extends TestCase
{
    private ActionDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container();
        $this->dispatcher = new ActionDispatcher($container);
    }

    public function test_dispatches_to_registered_handler(): void
    {
        $this->dispatcher->register('create_customer', FakeHandler::class);

        $dto = new AIResponseDTO(
            valid: true,
            action: 'create_customer',
            data: ['name' => 'Test'],
        );

        $result = $this->dispatcher->dispatch($dto);

        $this->assertTrue($result->success);
        $this->assertSame('Handled.', $result->message);
        $this->assertSame('/api/customers/1', $result->resourceUrl);
        $this->assertSame(['name' => 'Test'], $result->data);
    }

    public function test_throws_on_unknown_action(): void
    {
        $this->expectException(InvalidActionException::class);
        $this->expectExceptionMessage('Unknown action: unknown_action');

        $dto = new AIResponseDTO(
            valid: true,
            action: 'unknown_action',
            data: [],
        );

        $this->dispatcher->dispatch($dto);
    }

    public function test_throws_on_null_action(): void
    {
        $this->expectException(InvalidActionException::class);
        $this->expectExceptionMessage('Unknown action: null');

        $dto = new AIResponseDTO(
            valid: true,
            action: null,
            data: [],
        );

        $this->dispatcher->dispatch($dto);
    }

    public function test_multiple_handlers_registered(): void
    {
        $this->dispatcher->register('create_customer', FakeHandler::class);
        $this->dispatcher->register('update_customer', FakeHandler::class);

        $dto1 = new AIResponseDTO(valid: true, action: 'create_customer', data: ['a' => '1']);
        $dto2 = new AIResponseDTO(valid: true, action: 'update_customer', data: ['b' => '2']);

        $result1 = $this->dispatcher->dispatch($dto1);
        $result2 = $this->dispatcher->dispatch($dto2);

        $this->assertTrue($result1->success);
        $this->assertTrue($result2->success);
        $this->assertSame(['a' => '1'], $result1->data);
        $this->assertSame(['b' => '2'], $result2->data);
    }
}
