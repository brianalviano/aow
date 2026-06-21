<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Chef/Kitchen region.
 */
enum ChefRegion: string
{
    case SBY_TIMUR = 'sby_timur';
    case SBY_BARAT = 'sby_barat';
    case SBY_UTARA = 'sby_utara';
    case SBY_SELATAN = 'sby_selatan';
    case SBY_PUSAT = 'sby_pusat';

    /**
     * Get the label for the region.
     */
    public function label(): string
    {
        return match ($this) {
            self::SBY_TIMUR => 'Dapur SBY Timur',
            self::SBY_BARAT => 'Dapur SBY Barat',
            self::SBY_UTARA => 'Dapur SBY Utara',
            self::SBY_SELATAN => 'Dapur SBY Selatan',
            self::SBY_PUSAT => 'Dapur SBY Pusat',
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
