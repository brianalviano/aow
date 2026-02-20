<?php

namespace App\Enums;

enum StockOpnameStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Terjadwal',
            self::InProgress => 'Sedang Berlangsung',
            self::Completed => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Rencana stok opname belum aktif.',
            self::Scheduled => 'Stok opname dijadwalkan untuk dilaksanakan.',
            self::InProgress => 'Proses stok opname sedang berjalan.',
            self::Completed => 'Stok opname telah selesai.',
            self::Canceled => 'Stok opname dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
