<?php

namespace App\Enums;

enum LeaveRequestType: string
{
    case Sick = 'sick';
    case Leave = 'leave';
    case Permit = 'permit';

    public function label(): string
    {
        return match ($this) {
            self::Sick => 'Sakit',
            self::Leave => 'Cuti',
            self::Permit => 'Izin',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Sick => 'Permohonan izin tidak bekerja karena sakit.',
            self::Leave => 'Permohonan cuti sesuai kebijakan perusahaan.',
            self::Permit => 'Permohonan izin non-cuti (urusan pribadi/dinas).',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
