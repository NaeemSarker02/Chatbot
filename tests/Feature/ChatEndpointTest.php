<?php

namespace Tests\Feature;

use App\Models\ChatLog;
use App\Models\Customer;
use App\Services\AI\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatEndpointTest extends TestCase
{
    use RefreshDatabase;

    // ─── Success Scenarios ──────────────────────────────────────────

    public function test_create_customer_success(): void
    {
        $aiResponse = json_encode([
            'action' => 'create_customer',
            'data' => [
                'name' => 'Naeem Sarker',
                'email' => 'naeem@gmail.com',
                'phone' => '01678789233',
                'address' => 'Uttara, Dhaka',
            ],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Customer Entry: Naeem Sarker, naeem@gmail.com, 01678789233, Uttara, Dhaka',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer created successfully.',
            ])
            ->assertJsonStructure([
                'success', 'message', 'data' => ['id', 'name', 'email', 'phone', 'address'], 'url',
            ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Naeem Sarker',
            'email' => 'naeem@gmail.com',
        ]);

        $this->assertDatabaseHas('chat_logs', [
            'parsed_action' => 'create_customer',
            'status' => 'success',
        ]);
    }

    public function test_update_customer_success(): void
    {
        Customer::create([
            'name' => 'Naeem Sarker',
            'email' => 'naeem@gmail.com',
            'phone' => '01678789233',
            'address' => 'Uttara, Dhaka',
        ]);

        $aiResponse = json_encode([
            'action' => 'update_customer',
            'data' => [
                'email' => 'naeem@gmail.com',
                'phone' => '01999999999',
            ],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Update customer naeem@gmail.com phone to 01999999999',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated successfully.',
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'naeem@gmail.com',
            'phone' => '01999999999',
        ]);
    }

    public function test_delete_customer_success(): void
    {
        Customer::create([
            'name' => 'Naeem Sarker',
            'email' => 'naeem@gmail.com',
            'phone' => '01678789233',
        ]);

        $aiResponse = json_encode([
            'action' => 'delete_customer',
            'data' => ['email' => 'naeem@gmail.com'],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Delete customer naeem@gmail.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer deleted successfully.',
            ]);

        $this->assertSoftDeleted('customers', ['email' => 'naeem@gmail.com']);
    }

    public function test_read_customer_by_email(): void
    {
        Customer::create([
            'name' => 'Naeem Sarker',
            'email' => 'naeem@gmail.com',
            'phone' => '01678789233',
        ]);

        $aiResponse = json_encode([
            'action' => 'read_customer',
            'data' => ['email' => 'naeem@gmail.com'],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Show customer naeem@gmail.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer found.',
            ]);
    }

    public function test_read_all_customers(): void
    {
        Customer::create(['name' => 'A', 'email' => 'a@test.com', 'phone' => '111']);
        Customer::create(['name' => 'B', 'email' => 'b@test.com', 'phone' => '222']);

        $aiResponse = json_encode([
            'action' => 'read_customer',
            'data' => [],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Show all customers',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Customers retrieved.']);

        $this->assertCount(2, $response->json('data'));
    }

    // ─── AI Validation Error Scenarios ──────────────────────────────

    public function test_ai_returns_invalid_response(): void
    {
        $aiResponse = json_encode([
            'valid' => false,
            'error' => 'Invalid email and phone',
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Customer Entry: Naeem, wrongemail, 123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid email and phone',
            ]);

        $this->assertDatabaseHas('chat_logs', [
            'status' => 'invalid',
        ]);
    }

    // ─── Laravel Validation Errors (double validation) ──────────────

    public function test_duplicate_email_returns_validation_error(): void
    {
        Customer::create([
            'name' => 'Existing',
            'email' => 'naeem@gmail.com',
            'phone' => '01678789233',
        ]);

        $aiResponse = json_encode([
            'action' => 'create_customer',
            'data' => [
                'name' => 'Naeem Sarker',
                'email' => 'naeem@gmail.com',
                'phone' => '01678789233',
            ],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Customer Entry: Naeem Sarker, naeem@gmail.com, 01678789233',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        // Should NOT be a 500 — it should be a clean validation error
        $this->assertDatabaseHas('chat_logs', ['status' => 'failed']);
    }

    public function test_update_nonexistent_customer(): void
    {
        $aiResponse = json_encode([
            'action' => 'update_customer',
            'data' => [
                'email' => 'ghost@test.com',
                'name' => 'Ghost',
            ],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Update customer ghost@test.com name to Ghost',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Customer with email ghost@test.com not found.',
            ]);
    }

    public function test_delete_nonexistent_customer(): void
    {
        $aiResponse = json_encode([
            'action' => 'delete_customer',
            'data' => ['email' => 'ghost@test.com'],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Delete customer ghost@test.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Customer with email ghost@test.com not found.',
            ]);
    }

    // ─── Request Validation ─────────────────────────────────────────

    public function test_missing_message_returns_422(): void
    {
        $response = $this->postJson('/api/chat', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_empty_message_returns_422(): void
    {
        $response = $this->postJson('/api/chat', ['message' => '']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_message_too_long_returns_422(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    // ─── AI Failure Scenarios ───────────────────────────────────────

    public function test_malformed_ai_json_returns_502(): void
    {
        $this->mockAIService('not json at all!!!');

        $response = $this->postJson('/api/chat', [
            'message' => 'Customer Entry: Naeem Sarker',
        ]);

        $response->assertStatus(502)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('chat_logs', ['status' => 'failed']);
    }

    public function test_unknown_action_returns_422(): void
    {
        $aiResponse = json_encode([
            'action' => 'create_product',
            'data' => ['name' => 'Widget'],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $response = $this->postJson('/api/chat', [
            'message' => 'Create product widget',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('chat_logs', ['status' => 'failed']);
    }

    // ─── GET /api/customers/{id} ────────────────────────────────────

    public function test_get_customer_by_id(): void
    {
        $customer = Customer::create([
            'name' => 'Naeem Sarker',
            'email' => 'naeem@gmail.com',
            'phone' => '01678789233',
            'address' => 'Uttara, Dhaka',
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $customer->id,
                    'name' => 'Naeem Sarker',
                    'email' => 'naeem@gmail.com',
                ],
            ]);
    }

    public function test_get_nonexistent_customer_returns_404(): void
    {
        $response = $this->getJson('/api/customers/999');

        $response->assertStatus(404);
    }

    // ─── Chat Logs Audit ────────────────────────────────────────────

    public function test_every_interaction_is_logged(): void
    {
        $aiResponse = json_encode([
            'action' => 'create_customer',
            'data' => [
                'name' => 'Test',
                'email' => 'test@test.com',
                'phone' => '123',
            ],
            'valid' => true,
        ]);

        $this->mockAIService($aiResponse);

        $this->postJson('/api/chat', ['message' => 'Create test customer']);

        $this->assertDatabaseCount('chat_logs', 1);

        $log = ChatLog::first();
        $this->assertSame('Create test customer', $log->user_message);
        $this->assertSame($aiResponse, $log->ai_raw_response);
        $this->assertSame('create_customer', $log->parsed_action);
        $this->assertSame('success', $log->status);
        $this->assertNull($log->error_message);
    }

    // ─── Helper ─────────────────────────────────────────────────────

    private function mockAIService(string $returnBody): void
    {
        $mock = $this->createMock(AIService::class);
        $mock->method('chat')->willReturn($returnBody);
        $this->app->instance(AIService::class, $mock);
    }
}
