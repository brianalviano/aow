<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Payment Method Type.
 */
enum PaymentMethodType: string
{
    case MANUAL = 'manual';
    case GATEWAY = 'gateway';

    /**
     * Get the label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::GATEWAY => 'Otomatis',
        };
    }

    /**
     * Get the description for the type.
     */
    public function description(): string
    {
        return match ($this) {
            self::MANUAL => 'Pembayaran yang memerlukan verifikasi manual oleh admin.',
            self::GATEWAY => 'Pembayaran yang terintegrasi dengan payment gateway (otomatis).',
        };
    }

    /**
     * Get all values of the enum.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
