<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Chef Order Type.
 *
 * instant: Chef can accept instant orders and pre-orders.
 * preorder: Chef can only accept pre-orders.
 */
enum ChefOrderType: string
{
    case INSTANT = 'instant';
    case PREORDER = 'preorder';

    /**
     * Get the label for the order type.
     */
    public function label(): string
    {
        return match ($this) {
            self::INSTANT  => 'Bisa Instant & Pre-Order',
            self::PREORDER => 'Hanya Pre-Order',
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
