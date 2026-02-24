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
}
