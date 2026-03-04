<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enumeration for Drop Point categories.
 */
enum DropPointCategory: string
{
    case SCHOOL = 'school';
    case OFFICE = 'office';
    case RESIDENTIAL = 'residential';
    case SHOP = 'shop';
    case PUBLIC_SPACE = 'public_space';
    case HEALTHCARE = 'healthcare';
    case UNIVERSITY = 'university';
    case OFFICE_AREA = 'office_area';
    case SHOP_AREA = 'shop_area';
    case WORSHIP_PLACE = 'worship_place';
    case OTHER = 'other';

    /**
     * Get the human-readable label for the category.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::SCHOOL => 'Sekolah',
            self::OFFICE => 'Kantor',
            self::RESIDENTIAL => 'Perumahan',
            self::SHOP => 'Toko/Ruko',
            self::PUBLIC_SPACE => 'Area Publik',
            self::HEALTHCARE => 'Kesehatan',
            self::UNIVERSITY => 'Kampus / Universitas',
            self::OFFICE_AREA => 'Perkantoran',
            self::SHOP_AREA => 'Pertokoan',
            self::WORSHIP_PLACE => 'Peribadahan',
            self::OTHER => 'Lainnya',
        };
    }

    /**
     * Get the description for the category.
     */
    public function description(): string
    {
        return match ($this) {
            self::SCHOOL => 'Titik jemput di area sekolah atau institusi pendidikan.',
            self::OFFICE => 'Titik jemput di area perkantoran atau instansi.',
            self::RESIDENTIAL => 'Titik jemput di area perumahan atau permukiman.',
            self::SHOP => 'Titik jemput di area pertokoan atau ruko.',
            self::PUBLIC_SPACE => 'Titik jemput di fasilitas umum atau taman.',
            self::HEALTHCARE => 'Titik jemput di area rumah sakit atau klinik.',
            self::UNIVERSITY => 'Titik jemput di area kampus atau universitas.',
            self::OFFICE_AREA => 'Titik jemput di kawasan perkantoran.',
            self::SHOP_AREA => 'Titik jemput di kawasan pertokoan.',
            self::WORSHIP_PLACE => 'Titik jemput di tempat peribadahan.',
            self::OTHER => 'Titik jemput kategori lainnya.',
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

    /**
     * Get all categories as an array of options for a select input.
     *
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn(self $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ],
            self::cases()
        );
    }
}
