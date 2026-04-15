<?php

namespace App\Services\Actions\Handlers;

use App\DTOs\ActionResultDTO;
use App\Models\Customer;
use App\Services\Actions\Contracts\ActionHandlerInterface;
use App\Services\Customer\CustomerService;
use Illuminate\Support\Facades\Validator;

class ReadCustomerHandler implements ActionHandlerInterface
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function execute(array $data): ActionResultDTO
    {
        // If email is provided, find specific customer
        if (!empty($data['email'])) {
            $validator = Validator::make($data, [
                'email' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                return new ActionResultDTO(
                    success: false,
                    message: $validator->errors()->first(),
                );
            }

            $customer = $this->customerService->findByEmail($data['email']);

            if (!$customer) {
                return new ActionResultDTO(
                    success: false,
                    message: "Customer with email {$data['email']} not found.",
                );
            }

            return new ActionResultDTO(
                success: true,
                message: 'Customer found.',
                resourceUrl: "/api/customers/{$customer->id}",
                data: $customer->toArray(),
            );
        }

        // No email = return all customers
        $customers = Customer::all()->toArray();

        return new ActionResultDTO(
            success: true,
            message: 'Customers retrieved.',
            data: $customers,
        );
    }
}
