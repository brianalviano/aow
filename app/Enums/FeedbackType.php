<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Feedback Type.
 */
enum FeedbackType: string
{
    case KRITIK = 'kritik';
    case SARAN = 'saran';
    case LAINNYA = 'lainnya';

    /**
     * Get the label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::KRITIK => 'Kritik',
            self::SARAN => 'Saran',
            self::LAINNYA => 'Lainnya',
        };
    }

    /**
     * Get the description for the type.
     */
    public function description(): string
    {
        return match ($this) {
            self::KRITIK => 'Masukan berupa keluhan atau kekurangan.',
            self::SARAN => 'Ide atau usulan untuk pengembangan.',
            self::LAINNYA => 'Pesan lain di luar kritik dan saran.',
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
