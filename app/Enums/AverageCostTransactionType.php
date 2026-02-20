<?php

namespace App\Enums;

enum AverageCostTransactionType: string
{
    case Purchase = 'purchase';
    case Return = 'return';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Pembelian',
            self::Return => 'Retur',
            self::Adjustment => 'Penyesuaian',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Purchase => 'Perubahan biaya rata-rata karena pembelian.',
            self::Return => 'Perubahan biaya rata-rata karena retur.',
            self::Adjustment => 'Penyesuaian biaya rata-rata.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
