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

    /**
     * Get the description for the category.
     */
    public function description(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Pembayaran melalui transfer antar bank manual.',
            self::E_WALLET => 'Pembayaran menggunakan dompet digital (OVO, Dana, ShopeePay, dll).',
            self::VIRTUAL_ACCOUNT => 'Pembayaran otomatis melalui nomor Virtual Account.',
            self::CASH => 'Pembayaran tunai saat pengambilan atau pengantaran.',
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
