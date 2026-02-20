<?php

namespace App\Enums;

enum StockType: string
{
    case Main = 'main';
    case Consignment = 'consignment';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Utama',
            self::Consignment => 'Konsinyasi',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Main => 'Stok milik perusahaan.',
            self::Consignment => 'Stok titipan dari pemasok (konsinyasi).',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
