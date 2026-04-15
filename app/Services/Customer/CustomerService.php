<?php

namespace App\Services\Customer;

use App\Models\Customer;

class CustomerService
{
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(int $id, array $data): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->update($data);

        return $customer->refresh();
    }

    public function delete(int $id): bool
    {
        $customer = Customer::findOrFail($id);

        return $customer->delete();
    }

    public function find(int $id): Customer
    {
        return Customer::findOrFail($id);
    }

    public function findByEmail(string $email): ?Customer
    {
        return Customer::where('email', $email)->first();
    }
}
