<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Payment Status.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Pembayaran',
            self::PAID => 'Sudah Dibayar',
            self::FAILED => 'Gagal',
        };
    }

    /**
     * Get the description for the status.
     */
    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Pesanan sedang menunggu pembayaran dari pelanggan.',
            self::PAID => 'Pembayaran telah berhasil diverifikasi.',
            self::FAILED => 'Pembayaran gagal atau dibatalkan.',
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
