<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\FileHelperTrait;

class ProductResource extends JsonResource
{
    use FileHelperTrait;

    public function toArray(Request $request): array
    {
        /** @var \App\Models\Product $product */
        $product = $this->resource;

        return [
            'id' => (string) $product->id,
            'name' => (string) $product->name,
            'image_url' => $this->getFileUrl($product->image),
            'description' => (string) $product->description,
            'sku' => (string) $product->sku,
            'weight' => $product->weight !== null ? (float) $product->weight : null,
            'is_active' => (bool) $product->is_active,
            'product_type' => $product->product_type ?? null,
            'product_variant_type' => $product->product_variant_type ?? null,
            'min_stock' => $product->min_stock !== null ? (int) $product->min_stock : null,
            'max_stock' => $product->max_stock !== null ? (int) $product->max_stock : null,
            'category' => $product->productCategory ? [
                'id' => (string) $product->productCategory->id,
                'name' => (string) $product->productCategory->name,
            ] : ['id' => null, 'name' => null],
            'sub_category' => $product->productSubCategory ? [
                'id' => (string) $product->productSubCategory->id,
                'name' => (string) $product->productSubCategory->name,
            ] : ['id' => null, 'name' => null],
            'unit' => $product->productUnit ? [
                'id' => (string) $product->productUnit->id,
                'name' => (string) $product->productUnit->name,
            ] : ['id' => null, 'name' => null],
            'factory' => $product->productFactory ? [
                'id' => (string) $product->productFactory->id,
                'name' => (string) $product->productFactory->name,
            ] : ['id' => null, 'name' => null],
            'sub_factory' => $product->productSubFactory ? [
                'id' => (string) $product->productSubFactory->id,
                'name' => (string) $product->productSubFactory->name,
            ] : ['id' => null, 'name' => null],
            'condition' => $product->productCondition ? [
                'id' => (string) $product->productCondition->id,
                'name' => (string) $product->productCondition->name,
            ] : ['id' => null, 'name' => null],
            'parent' => $product->parentProduct ? [
                'id' => (string) $product->parentProduct->id,
                'name' => (string) $product->parentProduct->name,
                'sku' => (string) $product->parentProduct->sku,
            ] : ['id' => null, 'name' => null, 'sku' => null],
            'created_at' => $product->created_at ? $product->created_at->toDateTimeString() : null,
            'updated_at' => $product->updated_at ? $product->updated_at->toDateTimeString() : null,
        ];
    }
}
