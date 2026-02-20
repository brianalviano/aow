<?php

namespace App\Models;

use App\Enums\{ProductType, ProductVariantType};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'image',
        'description',
        'sku',
        'weight',
        'is_active',
        'product_category_id',
        'product_sub_category_id',
        'product_unit_id',
        'product_factory_id',
        'product_sub_factory_id',
        'product_condition_id',
        'product_type',
        'product_variant_type',
        'parent_product_id',
        'min_stock',
        'max_stock',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'double',
            'is_active' => 'boolean',
            'min_stock' => 'integer',
            'max_stock' => 'integer',
            'product_type' => ProductType::class,
            'product_variant_type' => ProductVariantType::class,
        ];
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function productSubCategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubCategory::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function productFactory(): BelongsTo
    {
        return $this->belongsTo(ProductFactory::class);
    }

    public function productSubFactory(): BelongsTo
    {
        return $this->belongsTo(ProductSubFactory::class);
    }

    public function productCondition(): BelongsTo
    {
        return $this->belongsTo(ProductCondition::class);
    }

    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
