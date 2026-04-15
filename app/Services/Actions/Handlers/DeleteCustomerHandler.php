<?php

namespace App\Services\Actions\Handlers;

use App\DTOs\ActionResultDTO;
use App\Services\Actions\Contracts\ActionHandlerInterface;
use App\Services\Customer\CustomerService;
use Illuminate\Support\Facades\Validator;

class DeleteCustomerHandler implements ActionHandlerInterface
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function execute(array $data): ActionResultDTO
    {
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

        $this->customerService->delete($customer->id);

        return new ActionResultDTO(
            success: true,
            message: 'Customer deleted successfully.',
        );
    }
}
