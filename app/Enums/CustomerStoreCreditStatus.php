<?php

namespace App\Enums;

enum CustomerStoreCreditStatus: string
{
    case Active = 'active';
    case UsedUp = 'used_up';
    case Expired = 'expired';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::UsedUp => 'Habis Terpakai',
            self::Expired => 'Kedaluwarsa',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Active => 'Kredit toko pelanggan masih dapat digunakan.',
            self::UsedUp => 'Kredit toko telah habis digunakan.',
            self::Expired => 'Kredit toko melewati masa berlaku.',
            self::Canceled => 'Kredit toko dibatalkan.',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
