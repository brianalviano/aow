<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Discount Type.
 */
enum DiscountType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    /**
     * Get the label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Persentase',
            self::FIXED => 'Potongan Tetap',
        };
    }

    /**
     * Get the description for the type.
     */
    public function description(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Potongan harga berdasarkan persentase dari total pesanan.',
            self::FIXED => 'Potongan harga dengan nilai nominal tetap.',
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
