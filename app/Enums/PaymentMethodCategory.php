<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Payment Method Category.
 */
enum PaymentMethodCategory: string
{
    case BANK_TRANSFER = 'bank-transfer';
    case E_WALLET = 'e-wallet';
    case VIRTUAL_ACCOUNT = 'virtual-account';
    case CASH = 'cash';

    /**
     * Get the label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank Transfer',
            self::E_WALLET => 'E-Wallet',
            self::VIRTUAL_ACCOUNT => 'Virtual Account',
            self::CASH => 'Tunai',
        };
    }
}
