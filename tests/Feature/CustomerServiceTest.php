<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\Customer\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CustomerService();
    }

    public function test_creates_customer(): void
    {
        $customer = $this->service->create([
            'name' => 'Naeem Sarker',
            'email' => 'naeem@gmail.com',
            'phone' => '01678789233',
            'address' => 'Uttara, Dhaka',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Naeem Sarker',
            'email' => 'naeem@gmail.com',
        ]);
    }

    public function test_updates_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'phone' => '123',
        ]);

        $updated = $this->service->update($customer->id, ['name' => 'Updated']);

        $this->assertSame('Updated', $updated->name);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated']);
    }

    public function test_soft_deletes_customer(): void
    {
        $customer = Customer::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'phone' => '123',
        ]);

        $result = $this->service->delete($customer->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_finds_customer_by_id(): void
    {
        $customer = Customer::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'phone' => '123',
        ]);

        $found = $this->service->find($customer->id);

        $this->assertSame($customer->id, $found->id);
    }

    public function test_finds_customer_by_email(): void
    {
        Customer::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'phone' => '123',
        ]);

        $found = $this->service->findByEmail('test@test.com');

        $this->assertNotNull($found);
        $this->assertSame('test@test.com', $found->email);
    }

    public function test_find_by_email_returns_null_for_nonexistent(): void
    {
        $found = $this->service->findByEmail('nonexistent@test.com');

        $this->assertNull($found);
    }
}
