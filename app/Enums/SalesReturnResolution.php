<?php

namespace App\Enums;

enum SalesReturnResolution: string
{
    case Refund = 'refund';
    case Exchange = 'exchange';
    case StoreCredit = 'store_credit';

    public function label(): string
    {
        return match ($this) {
            self::Refund => 'Refund',
            self::Exchange => 'Tukar Barang',
            self::StoreCredit => 'Kredit Toko',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Refund => 'Pengembalian dana kepada pelanggan.',
            self::Exchange => 'Penukaran dengan barang lain.',
            self::StoreCredit => 'Diberikan kredit toko untuk pembelian berikutnya.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
