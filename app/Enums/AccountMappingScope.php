<?php

namespace App\Enums;

enum AccountMappingScope: string
{
    case Global = 'global';
    case Warehouse = 'warehouse';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Global',
            self::Warehouse => 'Per Gudang',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Global => 'Mapping akun berlaku untuk seluruh sistem.',
            self::Warehouse => 'Mapping akun spesifik per gudang.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
