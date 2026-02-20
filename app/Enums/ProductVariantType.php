<?php

namespace App\Enums;

enum ProductVariantType: string
{
    case Standalone = 'standalone';
    case Parent = 'parent';
    case Variant = 'variant';

    public function label(): string
    {
        return match ($this) {
            self::Standalone => 'Mandiri',
            self::Parent => 'Induk',
            self::Variant => 'Varian',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Standalone => 'Produk berdiri sendiri tanpa varian.',
            self::Parent => 'Produk induk yang memiliki varian.',
            self::Variant => 'Produk turunan dari induk (varian).',
        };
    }

    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }
}
