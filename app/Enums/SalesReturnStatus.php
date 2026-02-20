<?php

namespace App\Enums;

enum SalesReturnStatus: string
{
    case Draft = 'draft';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Completed => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Retur penjualan belum diproses.',
            self::Completed => 'Retur penjualan selesai diproses.',
            self::Canceled => 'Retur penjualan dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
