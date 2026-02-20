<?php

namespace App\Enums;

enum StockAdjustmentStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Approved => 'Disetujui',
            self::Posted => 'Tercatat',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Penyesuaian stok belum disetujui.',
            self::Approved => 'Penyesuaian stok telah disetujui.',
            self::Posted => 'Penyesuaian stok telah dibukukan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
