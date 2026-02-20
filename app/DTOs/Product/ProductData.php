<?php

declare(strict_types=1);

namespace App\DTOs\Product;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use Illuminate\Http\UploadedFile;

class ProductData
{
    public function __construct(
        public string $name,
        public string $description,
        public string $sku,
        public ?float $weight,
        public ?bool $isActive,
        public ?string $productCategoryId,
        public ?string $productSubCategoryId,
        public ?string $productUnitId,
        public ?string $productFactoryId,
        public ?string $productSubFactoryId,
        public ?string $productConditionId,
        public ?string $productType,
        public ?string $productVariantType,
        public ?string $parentProductId,
        public ?int $minStock,
        public ?int $maxStock,
        public ?UploadedFile $image,
    ) {}

    public static function fromStoreRequest(StoreProductRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            description: (string) $p['description'],
            sku: (string) $p['sku'],
            weight: isset($p['weight']) ? (float) $p['weight'] : null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : null,
            productCategoryId: $p['product_category_id'] ?? null,
            productSubCategoryId: $p['product_sub_category_id'] ?? null,
            productUnitId: $p['product_unit_id'] ?? null,
            productFactoryId: $p['product_factory_id'] ?? null,
            productSubFactoryId: $p['product_sub_factory_id'] ?? null,
            productConditionId: $p['product_condition_id'] ?? null,
            productType: $p['product_type'] ?? null,
            productVariantType: $p['product_variant_type'] ?? null,
            parentProductId: $p['parent_product_id'] ?? null,
            minStock: isset($p['min_stock']) ? (int) $p['min_stock'] : null,
            maxStock: isset($p['max_stock']) ? (int) $p['max_stock'] : null,
            image: $request->file('image'),
        );
    }

    public static function fromUpdateRequest(UpdateProductRequest $request): self
    {
        $p = $request->validated();
        return new self(
            name: (string) $p['name'],
            description: (string) $p['description'],
            sku: (string) $p['sku'],
            weight: isset($p['weight']) ? (float) $p['weight'] : null,
            isActive: isset($p['is_active']) ? (bool) $p['is_active'] : null,
            productCategoryId: $p['product_category_id'] ?? null,
            productSubCategoryId: $p['product_sub_category_id'] ?? null,
            productUnitId: $p['product_unit_id'] ?? null,
            productFactoryId: $p['product_factory_id'] ?? null,
            productSubFactoryId: $p['product_sub_factory_id'] ?? null,
            productConditionId: $p['product_condition_id'] ?? null,
            productType: $p['product_type'] ?? null,
            productVariantType: $p['product_variant_type'] ?? null,
            parentProductId: $p['parent_product_id'] ?? null,
            minStock: isset($p['min_stock']) ? (int) $p['min_stock'] : null,
            maxStock: isset($p['max_stock']) ? (int) $p['max_stock'] : null,
            image: $request->file('image'),
        );
    }
}
