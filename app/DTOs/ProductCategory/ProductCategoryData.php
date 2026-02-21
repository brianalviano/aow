<?php

declare(strict_types=1);

namespace App\DTOs\ProductCategory;

use App\Http\Requests\Admin\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\Admin\ProductCategory\UpdateProductCategoryRequest;

/**
 * Data Transfer Object for ProductCategory.
 */
class ProductCategoryData
{
    public function __construct(
        public readonly string $name,
        public readonly bool $isActive = true,
        public readonly int $sortOrder = 0,
    ) {}

    /**
     * Create DTO from Store Form Request.
     *
     * @param StoreProductCategoryRequest $request
     * @return self
     */
    public static function fromStoreRequest(StoreProductCategoryRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            isActive: (bool) $request->validated('is_active', true),
            sortOrder: (int) $request->validated('sort_order', 0),
        );
    }

    /**
     * Create DTO from Update Form Request.
     *
     * @param UpdateProductCategoryRequest $request
     * @return self
     */
    public static function fromUpdateRequest(UpdateProductCategoryRequest $request): self
    {
        return new self(
            name: (string) $request->validated('name'),
            isActive: (bool) $request->validated('is_active', true),
            sortOrder: (int) $request->validated('sort_order', 0),
        );
    }
}
