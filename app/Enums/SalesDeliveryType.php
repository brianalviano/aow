<?php

namespace App\Enums;

enum SalesDeliveryType: string
{
    case WalkIn = 'walk_in';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::WalkIn => 'Ambil di Toko',
            self::Delivery => 'Diantar',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::WalkIn => 'Pelanggan mengambil barang di toko.',
            self::Delivery => 'Barang dikirim ke pelanggan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
