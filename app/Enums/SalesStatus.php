<?php

namespace App\Enums;

enum SalesStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case PartiallyDelivered = 'partially_delivered';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Confirmed => 'Terkonfirmasi',
            self::PartiallyDelivered => 'Terkirim Sebagian',
            self::Completed => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Penjualan belum dikonfirmasi.',
            self::Confirmed => 'Penjualan telah dikonfirmasi.',
            self::PartiallyDelivered => 'Sebagian barang sudah dikirim.',
            self::Completed => 'Penjualan selesai (barang terkirim seluruhnya).',
            self::Canceled => 'Penjualan dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
