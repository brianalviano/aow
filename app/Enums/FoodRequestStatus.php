<?php

declare(strict_types=1);

namespace App\Enums;

enum FoodRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::COMPLETED => 'Selesai',
        };
    }

    /**
     * Get the description for the status.
     */
    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Permintaan sedang menunggu peninjauan admin.',
            self::APPROVED => 'Permintaan telah disetujui.',
            self::REJECTED => 'Permintaan ditolak.',
            self::COMPLETED => 'Permintaan telah selesai diproses.',
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
