<?php

namespace App\Enums;

enum StockOpnameItemStatus: string
{
    case Pending = 'pending';
    case Counted = 'counted';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Counted => 'Terhitung',
            self::Verified => 'Terverifikasi',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Pending => 'Item belum dihitung.',
            self::Counted => 'Item sudah dihitung.',
            self::Verified => 'Hasil hitung telah diverifikasi.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
