<?php

namespace App\Enums;

enum NormalSide: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => 'Debit',
            self::Credit => 'Kredit',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Debit => 'Sisi normal saldo bertambah di debit.',
            self::Credit => 'Sisi normal saldo bertambah di kredit.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
