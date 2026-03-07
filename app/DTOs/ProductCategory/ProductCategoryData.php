<?php

declare(strict_types=1);

namespace App\DTOs\ProductCategory;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for ProductCategory create/update operations.
 *
 * @property string $name Nama kategori produk
 * @property bool $isActive Status aktif
 * @property int $sortOrder Urutan tampil
 */
class ProductCategoryData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isActive = true,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly int $sortOrder = 0,
    ) {}
}
