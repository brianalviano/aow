<?php

namespace App\Enums;

enum StockCardType: string
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
            self::In => 'Pergerakan stok masuk.',
            self::Out => 'Pergerakan stok keluar.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
