<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use Illuminate\Http\UploadedFile;

/**
 * Data Transfer Object for Product.
 */
class ProductData
{
    public function __construct(
        public readonly string $productCategoryId,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly int $price = 0,
        public readonly ?UploadedFile $image = null,
        public readonly ?int $stockLimit = null,
        public readonly bool $isActive = true,
        public readonly int $sortOrder = 0,
        /** @var array<int, ProductOptionData> */
        public readonly array $options = [],
    ) {}

    /**
     * Create DTO from Store Form Request.
     *
     * @param StoreProductRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreProductRequest $request): self
    {
        $optionsData = $request->validated('options', []);
        $options = [];
        if (is_array($optionsData)) {
            foreach ($optionsData as $optionData) {
                if (is_array($optionData)) {
                    $options[] = ProductOptionData::fromArray($optionData);
                }
            }
        }

        return new self(
            productCategoryId: (string) $request->validated('product_category_id'),
            name: (string) $request->validated('name'),
            description: $request->validated('description') !== null ? (string) $request->validated('description') : null,
            price: (int) $request->validated('price', 0),
            image: $request->file('image'),
            stockLimit: $request->validated('stock_limit') !== null ? (int) $request->validated('stock_limit') : null,
            isActive: (bool) $request->validated('is_active', true),
            sortOrder: (int) $request->validated('sort_order', 0),
            options: $options,
        );
    }

    /**
     * Create DTO from Update Form Request.
     *
     * @param UpdateProductRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateProductRequest $request): self
    {
        $optionsData = $request->validated('options', []);
        $options = [];
        if (is_array($optionsData)) {
            foreach ($optionsData as $optionData) {
                if (is_array($optionData)) {
                    $options[] = ProductOptionData::fromArray($optionData);
                }
            }
        }

        return new self(
            productCategoryId: (string) $request->validated('product_category_id'),
            name: (string) $request->validated('name'),
            description: $request->validated('description') !== null ? (string) $request->validated('description') : null,
            price: (int) $request->validated('price', 0),
            image: $request->file('image'),
            stockLimit: $request->validated('stock_limit') !== null ? (int) $request->validated('stock_limit') : null,
            isActive: (bool) $request->validated('is_active', true),
            sortOrder: (int) $request->validated('sort_order', 0),
            options: $options,
        );
    }
}
