<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for Product Option.
 *
 * Contains nested collection of ProductOptionItemData.
 *
 * @property string $name Nama opsi
 * @property bool $isRequired Apakah wajib dipilih
 * @property bool $isMultiple Apakah bisa pilih lebih dari satu
 * @property int $sortOrder Urutan tampil
 * @property array<int, ProductOptionItemData> $items Item-item opsi
 */
class ProductOptionData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isRequired = false,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isMultiple = false,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly int $sortOrder = 0,

        #[Rule('required', 'array', 'min:1')]
        #[DataCollectionOf(ProductOptionItemData::class)]
        public readonly array $items = [],
    ) {}
}
