<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for Product Option Item.
 *
 * @property string $name Nama item opsi
 * @property int $extraPrice Harga tambahan (IDR)
 * @property int $sortOrder Urutan tampil
 */
class ProductOptionItemData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly int $extraPrice = 0,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly int $sortOrder = 0,
    ) {}
}
