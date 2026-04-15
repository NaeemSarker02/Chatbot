<?php

namespace App\Services\Actions\Handlers;

use App\DTOs\ActionResultDTO;
use App\Services\Actions\Contracts\ActionHandlerInterface;
use App\Services\Customer\CustomerService;
use Illuminate\Support\Facades\Validator;

class UpdateCustomerHandler implements ActionHandlerInterface
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function execute(array $data): ActionResultDTO
    {
        $validator = Validator::make($data, [
            'email'   => 'required|email|max:255',
            'name'    => 'sometimes|string|max:255',
            'phone'   => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return new ActionResultDTO(
                success: false,
                message: $validator->errors()->first(),
            );
        }

        $validated = $validator->validated();
        $customer = $this->customerService->findByEmail($validated['email']);

        if (!$customer) {
            return new ActionResultDTO(
                success: false,
                message: "Customer with email {$validated['email']} not found.",
            );
        }

        $updateData = collect($validated)->except('email')->toArray();

        if (empty($updateData)) {
            return new ActionResultDTO(
                success: false,
                message: 'No fields provided to update.',
            );
        }

        $customer = $this->customerService->update($customer->id, $updateData);

        return new ActionResultDTO(
            success: true,
            message: 'Customer updated successfully.',
            resourceUrl: "/api/customers/{$customer->id}",
            data: $customer->toArray(),
        );
    }
}
