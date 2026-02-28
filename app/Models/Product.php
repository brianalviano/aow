<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany, HasManyThrough, HasOne};
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
     * Get the manipulation settings for this product.
     *
     * @return HasOne
     */
    public function manipulation(): HasOne
    {
        return $this->hasOne(ProductManipulation::class);
    }

    /**
     * Get the testimonials for this product.
     *
     * @return HasManyThrough
     */
    public function testimonials(): HasManyThrough
    {
        return $this->hasManyThrough(
            Testimonial::class,
            OrderItem::class,
            'product_id',    // Foreign key on OrderItem table...
            'order_item_id', // Foreign key on Testimonial table...
            'id',            // Local key on Product table...
            'id'             // Local key on OrderItem table...
        )->where('testimonials.is_approved', true);
    }

    /**
     * Get the total sales for this product.
     *
     * @return int
     */
    public function getTotalSalesAttribute(): int
    {
        $realSales = (int) $this->orderItems()
            ->whereHas('order', function ($query) {
                $query->whereIn('order_status', [
                    \App\Enums\OrderStatus::CONFIRMED->value,
                    \App\Enums\OrderStatus::SHIPPED->value,
                    \App\Enums\OrderStatus::DELIVERED->value,
                ]);
            })
            ->sum('quantity');

        $fakeSales = $this->manipulation?->is_active ? $this->manipulation->fake_sales_count : 0;

        return $realSales + $fakeSales;
    }

    /**
     * Get the average rating for this product.
     *
     * @return float
     */
    public function getAverageRatingAttribute(): float
    {
        $realStats = $this->testimonials()
            ->selectRaw('count(*) as count, sum(CAST(rating AS NUMERIC)) as sum')
            ->first();

        $realCount = (int) ($realStats->count ?? 0);
        $realSum = (float) ($realStats->sum ?? 0);

        $fakeCount = $this->manipulation?->is_active ? $this->manipulation->fake_testimonials_count : 0;

        if ($fakeCount > 0) {
            // We'll use the average of the templates to simulate the fake rating contribution
            $templateAvg = TestimonialTemplate::where('is_active', true)
                ->avg('rating') ?: 5.0;

            $fakeSum = $fakeCount * $templateAvg;

            $totalCount = $realCount + $fakeCount;
            $totalSum = $realSum + $fakeSum;

            return (float) ($totalSum / $totalCount);
        }

        return $realCount > 0 ? (float) ($realSum / $realCount) : 0.0;
    }

    /**
     * Get the testimonials count for this product.
     *
     * @return int
     */
    public function getTestimonialsCountAttribute(): int
    {
        $realCount = $this->testimonials()->count();
        $fakeCount = $this->manipulation?->is_active ? $this->manipulation->fake_testimonials_count : 0;

        return $realCount + $fakeCount;
    }

    /**
     * Get merged real and fake testimonials.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getManipulatedTestimonials(int $limit = 10)
    {
        $realTestimonials = $this->testimonials()
            ->with('customer')
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'customer_name' => $t->customer?->name ?? 'User',
                'rating' => $t->rating,
                'content' => $t->content,
                'photo' => $t->photo,
                'is_fake' => false,
                'created_at' => $t->created_at,
            ]);

        $fakeCount = $this->manipulation?->is_active ? $this->manipulation->fake_testimonials_count : 0;

        if ($fakeCount > 0) {
            $templates = TestimonialTemplate::where('is_active', true)
                ->inRandomOrder()
                ->limit($fakeCount)
                ->get()
                ->map(fn($t, $index) => [
                    'id' => 'fake-' . $t->id . '-' . $index,
                    'customer_name' => $t->customer_name,
                    'rating' => $t->rating,
                    'content' => $t->content,
                    'photo' => null,
                    'is_fake' => true,
                    // Simulate random dates in the past 30 days
                    'created_at' => now()->subDays(rand(1, 30))->subHours(rand(1, 23)),
                ]);

            $realTestimonials = $realTestimonials->concat($templates);
        }

        return $realTestimonials->sortByDesc('created_at')->take($limit)->values();
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
