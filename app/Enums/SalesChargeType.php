<?php

namespace App\Enums;

enum SalesChargeType: string
{
    case Shipping = 'shipping';
    case Service = 'service';
    case Rounding = 'rounding';
    case Packaging = 'packaging';
    case Insurance = 'insurance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Shipping => 'Biaya Pengiriman',
            self::Service => 'Biaya Layanan',
            self::Rounding => 'Pembulatan',
            self::Packaging => 'Pengemasan',
            self::Insurance => 'Asuransi',
            self::Other => 'Lainnya',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Shipping => 'Biaya pengiriman pesanan.',
            self::Service => 'Biaya layanan tambahan.',
            self::Rounding => 'Penyesuaian pembulatan nilai transaksi.',
            self::Packaging => 'Biaya bahan dan proses pengemasan.',
            self::Insurance => 'Biaya perlindungan risiko pengiriman.',
            self::Other => 'Biaya lain yang relevan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
