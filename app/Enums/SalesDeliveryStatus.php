<?php

namespace App\Enums;

enum SalesDeliveryStatus: string
{
    case Draft = 'draft';
    case InDelivery = 'in_delivery';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InDelivery => 'Dalam Pengiriman',
            self::Completed => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Dokumen pengiriman penjualan belum diproses.',
            self::InDelivery => 'Barang sedang dikirim ke pelanggan.',
            self::Completed => 'Pengiriman selesai dan barang diterima.',
            self::Canceled => 'Pengiriman dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
