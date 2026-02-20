<?php

namespace App\Enums;

enum PaymentDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Masuk',
            self::Out => 'Keluar',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::In => 'Pembayaran diterima dari pihak lain.',
            self::Out => 'Pembayaran dibayarkan ke pihak lain.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
