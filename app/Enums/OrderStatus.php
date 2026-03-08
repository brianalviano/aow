<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Order Status.
 *
 * Flow: PENDING → CONFIRMED → SHIPPED → AT_PICKUP_POINT → ON_DELIVERY → DELIVERED
 * CANCELLED can happen at any point before DELIVERED.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case SHIPPED = 'shipped';
    case AT_PICKUP_POINT = 'at_pickup_point';
    case ON_DELIVERY = 'on_delivery';
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
            self::SHIPPED => 'Dikirim ke Pickup Point',
            self::AT_PICKUP_POINT => 'Di Pickup Point',
            self::ON_DELIVERY => 'Sedang Dikirim ke Customer',
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
            self::CONFIRMED => 'Pesanan telah dikonfirmasi dan sedang diproses chef.',
            self::SHIPPED => 'Chef sedang mengirim makanan ke pickup point.',
            self::AT_PICKUP_POINT => 'Semua makanan sudah sampai di pickup point.',
            self::ON_DELIVERY => 'PIC sedang mengirim pesanan ke customer.',
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
