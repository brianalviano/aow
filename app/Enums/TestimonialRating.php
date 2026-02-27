<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Testimonial Rating.
 */
enum TestimonialRating: string
{
    case ONE = '1';
    case TWO = '2';
    case THREE = '3';
    case FOUR = '4';
    case FIVE = '5';

    /**
     * Get the label for the rating.
     */
    public function label(): string
    {
        return match ($this) {
            self::ONE => 'Sangat Buruk',
            self::TWO => 'Buruk',
            self::THREE => 'Cukup',
            self::FOUR => 'Baik',
            self::FIVE => 'Sangat Baik',
        };
    }

    /**
     * Get the description for the rating.
     */
    public function description(): string
    {
        return match ($this) {
            self::ONE => 'Pelayanan atau produk sangat mengecewakan.',
            self::TWO => 'Masih banyak kekurangan yang perlu diperbaiki.',
            self::THREE => 'Sudah cukup baik namun ada beberapa hal yang kurang.',
            self::FOUR => 'Puas dengan pelayanan dan kualitas produk.',
            self::FIVE => 'Luar biasa! Sangat merekomendasikan.',
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
