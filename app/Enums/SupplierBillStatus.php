<?php

namespace App\Enums;

enum SupplierBillStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Tercatat',
            self::PartiallyPaid => 'Terbayar Sebagian',
            self::Paid => 'Lunas',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Tagihan pemasok belum dibukukan.',
            self::Posted => 'Tagihan pemasok telah dibukukan.',
            self::PartiallyPaid => 'Tagihan terbayar sebagian.',
            self::Paid => 'Tagihan telah lunas.',
            self::Canceled => 'Tagihan dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
