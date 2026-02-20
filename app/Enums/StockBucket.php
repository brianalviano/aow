<?php

declare(strict_types=1);

namespace App\Enums;

enum StockBucket: string
{
    case Vat = 'vat';
    case NonVat = 'non_vat';

    public function label(): string
    {
        return match ($this) {
            self::Vat    => 'PPN',
            self::NonVat => 'Non PPN',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Vat    => 'Bucket stok dengan PPN.',
            self::NonVat => 'Bucket stok tanpa PPN.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
