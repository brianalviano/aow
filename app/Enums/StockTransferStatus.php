<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case Draft = 'draft';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InTransit => 'Dalam Perjalanan',
            self::Received => 'Diterima',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Mutasi antar gudang belum diproses.',
            self::InTransit => 'Barang sedang dipindahkan antar gudang.',
            self::Received => 'Barang telah diterima di gudang tujuan.',
            self::Canceled => 'Mutasi antar gudang dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
