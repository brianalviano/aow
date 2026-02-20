<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Tercatat',
            self::Void => 'Batal',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Transaksi pembayaran belum dibukukan.',
            self::Posted => 'Transaksi pembayaran telah dibukukan.',
            self::Void => 'Transaksi pembayaran dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
