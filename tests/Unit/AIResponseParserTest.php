<?php

namespace Tests\Unit;

use App\DTOs\AIResponseDTO;
use App\Exceptions\AIResponseException;
use App\Services\AI\AIResponseParser;
use PHPUnit\Framework\TestCase;

class AIResponseParserTest extends TestCase
{
    private AIResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AIResponseParser();
    }

    public function test_parses_valid_create_customer_response(): void
    {
        $json = json_encode([
            'action' => 'create_customer',
            'data' => [
                'name' => 'Naeem Sarker',
                'email' => 'naeem@gmail.com',
                'phone' => '01678789233',
                'address' => 'Uttara, Dhaka',
            ],
            'valid' => true,
        ]);

        $dto = $this->parser->parse($json);

        $this->assertTrue($dto->valid);
        $this->assertSame('create_customer', $dto->action);
        $this->assertSame('Naeem Sarker', $dto->data['name']);
        $this->assertSame('naeem@gmail.com', $dto->data['email']);
        $this->assertSame('01678789233', $dto->data['phone']);
        $this->assertSame('Uttara, Dhaka', $dto->data['address']);
        $this->assertNull($dto->error);
    }

    public function test_parses_invalid_response(): void
    {
        $json = json_encode([
            'valid' => false,
            'error' => 'Invalid email format',
        ]);

        $dto = $this->parser->parse($json);

        $this->assertFalse($dto->valid);
        $this->assertSame('Invalid email format', $dto->error);
        $this->assertNull($dto->action);
        $this->assertEmpty($dto->data);
    }

    public function test_parses_invalid_response_without_error_message(): void
    {
        $json = json_encode(['valid' => false]);

        $dto = $this->parser->parse($json);

        $this->assertFalse($dto->valid);
        $this->assertSame('Unknown validation error from AI.', $dto->error);
    }

    public function test_throws_on_malformed_json(): void
    {
        $this->expectException(AIResponseException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $this->parser->parse('not valid json {{{');
    }

    public function test_throws_on_missing_valid_field(): void
    {
        $this->expectException(AIResponseException::class);
        $this->expectExceptionMessage('Missing "valid" field');

        $this->parser->parse(json_encode(['action' => 'create_customer']));
    }

    public function test_throws_on_valid_true_without_action(): void
    {
        $this->expectException(AIResponseException::class);
        $this->expectExceptionMessage('Missing "action" field');

        $this->parser->parse(json_encode([
            'valid' => true,
            'data' => ['name' => 'Test'],
        ]));
    }

    public function test_throws_on_valid_true_without_data(): void
    {
        $this->expectException(AIResponseException::class);
        $this->expectExceptionMessage('Missing or invalid "data" field');

        $this->parser->parse(json_encode([
            'valid' => true,
            'action' => 'create_customer',
        ]));
    }

    public function test_strips_markdown_code_fences(): void
    {
        $raw = "```json\n" . json_encode([
            'action' => 'create_customer',
            'data' => ['name' => 'Test', 'email' => 'test@test.com', 'phone' => '123'],
            'valid' => true,
        ]) . "\n```";

        $dto = $this->parser->parse($raw);

        $this->assertTrue($dto->valid);
        $this->assertSame('create_customer', $dto->action);
    }

    public function test_strips_markdown_code_fences_without_json_label(): void
    {
        $raw = "```\n" . json_encode([
            'valid' => false,
            'error' => 'unclear input',
        ]) . "\n```";

        $dto = $this->parser->parse($raw);

        $this->assertFalse($dto->valid);
        $this->assertSame('unclear input', $dto->error);
    }

    public function test_handles_empty_string(): void
    {
        $this->expectException(AIResponseException::class);

        $this->parser->parse('');
    }

    public function test_parses_update_customer_action(): void
    {
        $json = json_encode([
            'action' => 'update_customer',
            'data' => [
                'email' => 'naeem@gmail.com',
                'phone' => '01999999999',
            ],
            'valid' => true,
        ]);

        $dto = $this->parser->parse($json);

        $this->assertTrue($dto->valid);
        $this->assertSame('update_customer', $dto->action);
        $this->assertSame('naeem@gmail.com', $dto->data['email']);
        $this->assertSame('01999999999', $dto->data['phone']);
    }

    public function test_parses_delete_customer_action(): void
    {
        $json = json_encode([
            'action' => 'delete_customer',
            'data' => ['email' => 'naeem@gmail.com'],
            'valid' => true,
        ]);

        $dto = $this->parser->parse($json);

        $this->assertTrue($dto->valid);
        $this->assertSame('delete_customer', $dto->action);
    }
}
