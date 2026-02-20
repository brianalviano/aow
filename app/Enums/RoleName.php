<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Director = 'Director';

        // HR
    case ManagerHR = 'Manager HR';

        // Logistic
    case ManagerLogistic = 'Manager Logistic';
    case StaffLogistic = 'Staff Logistic';

        // Sales
    case ManagerSales = 'Manager Sales';
    case Cashier = 'Cashier';
    case Sales = 'Sales';
    case Marketing = 'Marketing';

        // Finance
    case ManagerFinance = 'Manager Finance';
    case StaffFinance = 'Staff Finance';

    public static function values(): array
    {
        return array_map(static fn(self $c) => $c->value, self::cases());
    }

    public static function highest(): array
    {
        return [
            self::SuperAdmin->value,
            self::Director->value,
        ];
    }

    public static function stockOpnameAssignable(): array
    {
        return [
            self::ManagerLogistic->value,
            self::StaffLogistic->value,
            self::ManagerSales->value,
            self::Cashier->value,
            self::Sales->value,
            self::Marketing->value,
        ];
    }
}
