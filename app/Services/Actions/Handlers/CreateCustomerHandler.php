<?php

namespace App\Services\Actions\Handlers;

use App\DTOs\ActionResultDTO;
use App\Services\Actions\Contracts\ActionHandlerInterface;
use App\Services\Customer\CustomerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateCustomerHandler implements ActionHandlerInterface
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function execute(array $data): ActionResultDTO
    {
        $validator = Validator::make($data, [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:customers,email|max:255',
            'phone'   => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return new ActionResultDTO(
                success: false,
                message: $validator->errors()->first(),
            );
        }

        $customer = $this->customerService->create($validator->validated());

        return new ActionResultDTO(
            success: true,
            message: 'Customer created successfully.',
            resourceUrl: "/api/customers/{$customer->id}",
            data: $customer->toArray(),
        );
    }
}
