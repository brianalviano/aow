<?php

namespace App\Enums;

enum Currency: string
{
    case Idr = 'idr';
    case Usd = 'usd';

    public function label(): string
    {
        return match ($this) {
            self::Idr => 'Rupiah (IDR)',
            self::Usd => 'Dolar AS (USD)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Idr => 'Mata uang Rupiah, tanpa pecahan sen dalam praktik.',
            self::Usd => 'Mata uang Dolar Amerika Serikat.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
