<?php

namespace App\Enums;

enum CustomerSource: string
{
    case Pos = 'pos';
    case Marketing = 'marketing';

    public function label(): string
    {
        return match ($this) {
            self::Pos => 'POS',
            self::Marketing => 'Marketing',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
