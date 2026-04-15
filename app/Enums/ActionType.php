<?php

namespace App\Enums;

enum ActionType: string
{
    case CREATE_CUSTOMER = 'create_customer';
    case UPDATE_CUSTOMER = 'update_customer';
    case DELETE_CUSTOMER = 'delete_customer';
    case READ_CUSTOMER = 'read_customer';

    public static function fromString(string $action): self
    {
        return self::from($action);
    }
}
