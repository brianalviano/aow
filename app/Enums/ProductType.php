<?php

namespace App\Enums;

enum ProductType: string
{
    case Raw = 'raw';
    case Ready = 'ready';

    public function label(): string
    {
        return match ($this) {
            self::Raw => 'Bahan Baku',
            self::Ready => 'Barang Jadi',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Raw => 'Produk berupa bahan baku atau komponen.',
            self::Ready => 'Produk siap jual atau siap pakai.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
