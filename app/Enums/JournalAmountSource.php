<?php

namespace App\Enums;

enum JournalAmountSource: string
{
    case Subtotal = 'subtotal';
    case GrandTotal = 'grand_total';
    case VatAmount = 'vat_amount';
    case DiscountAmount = 'discount_amount';
    case CostAmount = 'cost_amount';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Subtotal => 'Subtotal',
            self::GrandTotal => 'Grand Total',
            self::VatAmount => 'PPN',
            self::DiscountAmount => 'Diskon',
            self::CostAmount => 'Biaya/Cost',
            self::Custom => 'Kustom',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Subtotal => 'Mengambil nilai subtotal transaksi.',
            self::GrandTotal => 'Mengambil nilai total akhir transaksi.',
            self::VatAmount => 'Mengambil jumlah pajak (PPN).',
            self::DiscountAmount => 'Mengambil total diskon.',
            self::CostAmount => 'Mengambil nilai biaya/harga pokok.',
            self::Custom => 'Nilai diisi kustom sesuai aturan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
