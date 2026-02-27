<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Order Status.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Konfirmasi',
            self::CONFIRMED => 'Dikonfirmasi',
            self::SHIPPED => 'Dikirim',
            self::DELIVERED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    /**
     * Get the description for the status.
     */
    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Pesanan baru masuk dan menunggu konfirmasi admin.',
            self::CONFIRMED => 'Pesanan telah dikonfirmasi dan sedang diproses.',
            self::SHIPPED => 'Pesanan sedang dalam proses pengiriman.',
            self::DELIVERED => 'Pesanan telah diterima oleh pelanggan.',
            self::CANCELLED => 'Pesanan telah dibatalkan.',
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
