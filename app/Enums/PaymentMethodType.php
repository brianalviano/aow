<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Payment Method Type.
 */
enum PaymentMethodType: string
{
    case CASH = 'cash';
    case ONLINE = 'online';

    /**
     * Get the label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Tunai (Cash)',
            self::ONLINE => 'Online / Transfer',
        };
    }
}
