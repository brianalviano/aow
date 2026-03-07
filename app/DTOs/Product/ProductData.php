<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for Product create/update operations.
 *
 * Contains nested collection of ProductOptionData for product customizations.
 *
 * @property string $productCategoryId UUID dari kategori produk
 * @property string $name Nama produk
 * @property string|null $description Deskripsi produk
 * @property int $price Harga produk (IDR)
 * @property UploadedFile|null $image Gambar produk
 * @property int|null $stockLimit Batas stok (null = unlimited)
 * @property bool $isActive Status aktif
 * @property int $sortOrder Urutan tampil
 * @property array<int, ProductOptionData> $options Opsi produk (varian, topping, dll)
 * @property int $fakeSalesCount Jumlah penjualan palsu
 * @property int $fakeTestimonialsCount Jumlah testimoni palsu
 * @property bool $isManipulationActive Status manipulasi aktif
 */
class ProductData extends Data
{
    public function __construct(
        #[Rule('required', 'string', 'uuid', 'exists:product_categories,id')]
        public readonly string $productCategoryId,

        #[Rule('required', 'string', 'max:255')]
        public readonly string $name,

        #[Rule('nullable', 'string')]
        public readonly ?string $description = null,

        #[Rule('required', 'integer', 'min:0')]
        public readonly int $price = 0,

        #[Rule('nullable', 'image', 'max:2048')]
        public readonly ?UploadedFile $image = null,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly ?int $stockLimit = null,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isActive = true,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly int $sortOrder = 0,

        #[Rule('nullable', 'array')]
        #[DataCollectionOf(ProductOptionData::class)]
        public readonly array $options = [],

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly int $fakeSalesCount = 0,

        #[Rule('nullable', 'integer', 'min:0')]
        public readonly int $fakeTestimonialsCount = 0,

        #[Rule('sometimes', 'boolean')]
        public readonly bool $isManipulationActive = false,
    ) {}
}
