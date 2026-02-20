<?php

namespace App\Enums;

enum StockAdjustmentReason: string
{
    case Damage = 'damage';
    case Expired = 'expired';
    case Shrinkage = 'shrinkage';
    case ManualCorrection = 'manual_correction';
    case OpeningBalance = 'opening_balance';

    public function label(): string
    {
        return match ($this) {
            self::Damage => 'Kerusakan',
            self::Expired => 'Kedaluwarsa',
            self::Shrinkage => 'Susut',
            self::ManualCorrection => 'Koreksi Manual',
            self::OpeningBalance => 'Saldo Awal',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Damage => 'Penyesuaian karena barang rusak.',
            self::Expired => 'Penyesuaian karena barang kedaluwarsa.',
            self::Shrinkage => 'Penyesuaian karena susut stok.',
            self::ManualCorrection => 'Penyesuaian manual untuk koreksi.',
            self::OpeningBalance => 'Penetapan saldo awal persediaan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
