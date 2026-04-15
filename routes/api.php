<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Support\Facades\Route;

Route::post('/chat', [ChatController::class, 'handle']);

Route::get('/customers/{customer}', function (Customer $customer) {
    return new CustomerResource($customer);
});
