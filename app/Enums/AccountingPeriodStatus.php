<?php

namespace App\Enums;

enum AccountingPeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Closed => 'Tertutup',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Open => 'Periode akuntansi aktif dan dapat menerima posting.',
            self::Closed => 'Periode ditutup dan tidak dapat diubah.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
