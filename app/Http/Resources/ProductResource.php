<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Traits\FileHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    use FileHelperTrait;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_category_id' => $this->product_category_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image' => $this->image,
            'image_url' => $this->getFileUrl($this->image),
            'stock_limit' => $this->stock_limit,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product_category' => new ProductCategoryResource($this->whenLoaded('productCategory')),
        ];
    }
}
