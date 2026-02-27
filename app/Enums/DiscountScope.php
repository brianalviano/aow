<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Discount Scope.
 */
enum DiscountScope: string
{
    case GLOBAL = 'global';
    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case DROP_POINT = 'drop_point';
    case SHIPPING = 'shipping';

    /**
     * Get the label for the scope.
     */
    public function label(): string
    {
        return match ($this) {
            self::GLOBAL => 'Global',
            self::PRODUCT => 'Produk',
            self::CATEGORY => 'Kategori',
            self::DROP_POINT => 'Drop Point',
            self::SHIPPING => 'Pengiriman',
        };
    }

    /**
     * Get the description for the scope.
     */
    public function description(): string
    {
        return match ($this) {
            self::GLOBAL => 'Berlaku untuk seluruh pesanan tanpa syarat kategori.',
            self::PRODUCT => 'Berlaku khusus untuk produk tertentu.',
            self::CATEGORY => 'Berlaku untuk kategori produk tertentu.',
            self::DROP_POINT => 'Berlaku khusus pada lokasi drop point tertentu.',
            self::SHIPPING => 'Berlaku khusus untuk potongan biaya pengiriman.',
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
