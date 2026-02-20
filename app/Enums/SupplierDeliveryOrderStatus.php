<?php

namespace App\Enums;

enum SupplierDeliveryOrderStatus: string
{
    case Draft = 'draft';
    case InDelivery = 'in_delivery';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InDelivery => 'Dalam Pengiriman',
            self::Completed => 'Selesai',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Dokumen pengiriman pemasok belum diproses.',
            self::InDelivery => 'Barang sedang dalam proses pengiriman oleh pemasok.',
            self::Completed => 'Pengiriman selesai dan diterima.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
