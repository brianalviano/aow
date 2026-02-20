<?php

namespace App\Enums;

enum SalesPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::PartiallyPaid => 'Terbayar Sebagian',
            self::Paid => 'Lunas',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum ada pembayaran diterima.',
            self::PartiallyPaid => 'Pembayaran sebagian telah diterima.',
            self::Paid => 'Pembayaran telah lunas.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
