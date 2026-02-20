<?php

namespace App\Enums;

enum SalesReturnReason: string
{
    case Damaged = 'damaged';
    case WrongItem = 'wrong_item';
    case Expired = 'expired';
    case CustomerCancel = 'customer_cancel';
    case ExcessQuantity = 'excess_quantity';
    case Others = 'others';

    public function label(): string
    {
        return match ($this) {
            self::Damaged => 'Rusak',
            self::WrongItem => 'Salah Barang',
            self::Expired => 'Kedaluwarsa',
            self::CustomerCancel => 'Pembatalan Pelanggan',
            self::ExcessQuantity => 'Kelebihan Kuantitas',
            self::Others => 'Lainnya',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Damaged => 'Barang rusak saat diterima atau setelah penjualan.',
            self::WrongItem => 'Barang tidak sesuai pesanan.',
            self::Expired => 'Barang melewati tanggal kedaluwarsa.',
            self::CustomerCancel => 'Pesanan dibatalkan oleh pelanggan.',
            self::ExcessQuantity => 'Jumlah barang melebihi yang dibutuhkan.',
            self::Others => 'Alasan lain di luar kategori utama.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
