<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';

    public static function values(): array
    {
        return array_map(static fn(self $c) => $c->value, self::cases());
    }
}
