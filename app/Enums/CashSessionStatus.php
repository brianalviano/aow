<?php

namespace App\Enums;

enum CashSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Closed => 'Tertutup',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Open => 'Shift kasir aktif berjalan.',
            self::Closed => 'Shift kasir ditutup.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
