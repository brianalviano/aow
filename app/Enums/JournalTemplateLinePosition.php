<?php

namespace App\Enums;

enum JournalTemplateLinePosition: string
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
            self::Debit => 'Posisi baris jurnal di sisi debit.',
            self::Credit => 'Posisi baris jurnal di sisi kredit.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
