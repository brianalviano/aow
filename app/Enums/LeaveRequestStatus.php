<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Pending => 'Permohonan sedang menunggu persetujuan.',
            self::Approved => 'Permohonan telah disetujui.',
            self::Rejected => 'Permohonan telah ditolak.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
