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
            'chefs' => ChefResource::collection($this->whenLoaded('chefs')),
            'product_category' => new ProductCategoryResource($this->whenLoaded('productCategory')),
            'options' => ProductOptionResource::collection($this->whenLoaded('productOptions')),
            'testimonials' => TestimonialResource::collection($this->whenLoaded('testimonials')),
            'total_sales' => $this->total_sales,
            'average_rating' => $this->average_rating,
            'testimonials_count' => $this->testimonials_count,
            'manipulation' => $this->manipulation ? [
                'fake_sales_count' => $this->manipulation->fake_sales_count,
                'fake_testimonials_count' => $this->manipulation->fake_testimonials_count,
                'is_active' => $this->manipulation->is_active,
            ] : [
                'fake_sales_count' => 0,
                'fake_testimonials_count' => 0,
                'is_active' => false,
            ],
        ];
    }
}
