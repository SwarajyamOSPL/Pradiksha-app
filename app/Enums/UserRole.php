<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case SalesManager = 'sales_manager';
    case FranchiseHolder = 'franchise_holder';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::SalesManager => 'Sales Manager',
            self::FranchiseHolder => 'Franchise Holder',
        };
    }
}
