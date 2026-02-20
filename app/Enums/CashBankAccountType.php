<?php

namespace App\Enums;

enum CashBankAccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Kas',
            self::Bank => 'Bank',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cash => 'Rekening kas fisik.',
            self::Bank => 'Rekening bank.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
