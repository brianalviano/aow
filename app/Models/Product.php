<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes, FileHelperTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_category_id',
        'name',
        'description',
        'price',
        'image',
        'stock_limit',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Get the options available for this product.
     *
     * @return HasMany
     */
    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    /**
     * Chef-chef yang di-assign ke produk ini.
     */
    public function chefs(): BelongsToMany
    {
        return $this->belongsToMany(Chef::class)->withTimestamps();
    }

    /**
     * Get the order items for this product.
     *
     * @return HasMany
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the testimonials for this product.
     *
     * @return HasMany
     */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class, 'product_id', 'id')
            ->join('order_items', 'testimonials.order_item_id', '=', 'order_items.id')
            ->where('order_items.product_id', $this->id)
            ->where('testimonials.is_approved', true)
            ->select('testimonials.*'); // Ensure we only get testimonial columns
    }

    /**
     * Get the total sales for this product.
     *
     * @return int
     */
    public function getTotalSalesAttribute(): int
    {
        return (int) $this->orderItems()
            ->whereHas('order', function ($query) {
                // Assuming 'completed' or similar status means a successful sale
                $query->whereIn('order_status', ['completed', 'processing', 'shipped']);
            })
            ->sum('quantity');
    }

    /**
     * Get the average rating for this product.
     *
     * @return float
     */
    public function getAverageRatingAttribute(): float
    {
        return (float) $this->testimonials()->avg('rating') ?: 0.0;
    }

    /**
     * Get the testimonials count for this product.
     *
     * @return int
     */
    public function getTestimonialsCountAttribute(): int
    {
        return $this->testimonials()->count();
    }

    /**
     * Get the image URL.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function getImageAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }
}
