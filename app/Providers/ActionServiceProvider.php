<?php

namespace App\Providers;

use App\Enums\ActionType;
use App\Services\Actions\ActionDispatcher;
use App\Services\Actions\Handlers\CreateCustomerHandler;
use App\Services\Actions\Handlers\DeleteCustomerHandler;
use App\Services\Actions\Handlers\ReadCustomerHandler;
use App\Services\Actions\Handlers\UpdateCustomerHandler;
use Illuminate\Support\ServiceProvider;

class ActionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActionDispatcher::class, function ($app) {
            $dispatcher = new ActionDispatcher($app);

            $dispatcher->register(ActionType::CREATE_CUSTOMER->value, CreateCustomerHandler::class);
            $dispatcher->register(ActionType::UPDATE_CUSTOMER->value, UpdateCustomerHandler::class);
            $dispatcher->register(ActionType::DELETE_CUSTOMER->value, DeleteCustomerHandler::class);
            $dispatcher->register(ActionType::READ_CUSTOMER->value, ReadCustomerHandler::class);

            return $dispatcher;
        });
    }
}
