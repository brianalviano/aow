<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Shipping Method.
 */
enum ShippingMethod: string
{
    case ONLINE = 'online';
    case FREE = 'free';

    /**
     * Get the label for the method.
     */
    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'Online',
            self::FREE => 'Gratis Ongkir',
        };
    }

    /**
     * Get the description for the method.
     */
    public function description(): string
    {
        return match ($this) {
            self::ONLINE => 'Pengiriman menggunakan kurir online (Grab/Gojek).',
            self::FREE => 'Layanan pengiriman gratis ke drop point.',
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
