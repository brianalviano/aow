<?php

namespace App\Enums;

enum PurchaseReturnReason: string
{
    case Damaged = 'damaged';
    case WrongItem = 'wrong_item';
    case Expired = 'expired';
    case Others = 'others';

    public function label(): string
    {
        return match ($this) {
            self::Damaged => 'Rusak',
            self::WrongItem => 'Salah Barang',
            self::Expired => 'Kedaluwarsa',
            self::Others => 'Lainnya',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Damaged => 'Barang rusak saat diterima dari pemasok.',
            self::WrongItem => 'Barang yang dikirim tidak sesuai pesanan.',
            self::Expired => 'Barang melewati tanggal kedaluwarsa saat diterima.',
            self::Others => 'Alasan lain di luar kategori utama.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
