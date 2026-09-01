<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Order Status.
 *
 * Flow: PENDING → CONFIRMED → ON_DELIVERY → ARRIVED → DELIVERED
 * CANCELLED can happen at any point before DELIVERED.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COOKING = 'cooking';
    case ON_DELIVERY = 'on_delivery';
    case ARRIVED = 'arrived';
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
            self::COOKING => 'Sedang Dimasak',
            self::ON_DELIVERY => 'Sedang Dikirim',
            self::ARRIVED => 'Tiba di Tujuan',
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
            self::CONFIRMED => 'Pesanan telah dikonfirmasi dan menunggu antrean masak.',
            self::COOKING => 'Pesanan sedang dimasak dan disiapkan di dapur utama.',
            self::ON_DELIVERY => 'Pesanan sedang dalam pengiriman ke customer / drop point.',
            self::ARRIVED => 'Pesanan telah sampai dan menunggu konfirmasi pelanggan.',
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
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
