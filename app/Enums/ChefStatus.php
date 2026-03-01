<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Chef confirmation status on an order.
 */
enum ChefStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Konfirmasi Chef',
            self::ACCEPTED => 'Diterima Chef',
            self::REJECTED => 'Ditolak Chef',
        };
    }

    /**
     * Get all values of the enum.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
