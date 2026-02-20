<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case Permit = 'permit';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Late => 'Terlambat',
            self::Absent => 'Absen',
            self::Permit => 'Izin',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Present => 'Kehadiran tepat waktu sesuai jadwal.',
            self::Late => 'Kehadiran melebihi waktu mulai yang ditentukan.',
            self::Absent => 'Tidak hadir tanpa keterangan atau konfirmasi.',
            self::Permit => 'Tidak hadir dengan izin resmi (izin harian).',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'text-green-600',
            self::Late => 'text-yellow-600',
            self::Absent => 'text-red-600',
            self::Permit => 'text-blue-600',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
