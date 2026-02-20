<?php

namespace App\Enums;

enum StockAdjustmentType: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';

    public function label(): string
    {
        return match ($this) {
            self::Increase => 'Penambahan',
            self::Decrease => 'Pengurangan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Increase => 'Menambah jumlah stok.',
            self::Decrease => 'Mengurangi jumlah stok.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
