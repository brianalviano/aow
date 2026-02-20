<?php

namespace App\Enums;

enum StockOpnameAssignmentStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Assigned => 'Ditugaskan',
            self::Completed => 'Selesai',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Pending => 'Belum ada penugasan hitung stok.',
            self::Assigned => 'Sudah ditugaskan untuk hitung stok.',
            self::Completed => 'Penugasan hitung stok selesai.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
