<?php

namespace App\Enums;

enum PurchaseReturnStatus: string
{
    case Draft = 'draft';
    case InDelivery = 'in_delivery';
    case PartiallyDelivered = 'partially_delivered';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InDelivery => 'Dalam Pengiriman',
            self::PartiallyDelivered => 'Terkirim Sebagian',
            self::Completed => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Retur pembelian belum diproses.',
            self::InDelivery => 'Barang retur sedang dikirim ke pemasok.',
            self::PartiallyDelivered => 'Sebagian barang retur sudah dikirim/diterima.',
            self::Completed => 'Retur pembelian selesai diproses.',
            self::Canceled => 'Retur pembelian dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
