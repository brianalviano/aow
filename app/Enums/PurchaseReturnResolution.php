<?php

namespace App\Enums;

enum PurchaseReturnResolution: string
{
    case Discount = 'discount';
    case Replace = 'replace';
    case CreditNote = 'credit_note';

    public function label(): string
    {
        return match ($this) {
            self::Discount => 'Diskon',
            self::Replace => 'Penggantian Barang',
            self::CreditNote => 'Nota Kredit',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Discount => 'Pemberian diskon oleh pemasok pada tagihan/pembelian.',
            self::Replace => 'Penggantian barang oleh pemasok.',
            self::CreditNote => 'Penerbitan kredit oleh pemasok untuk tagihan berikutnya.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
