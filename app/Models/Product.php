<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Product extends Model
{
    use FileHelperTrait, HasFactory, HasUuids, SoftDeletes;

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
        'cost_price',
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
     */
    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the manipulation settings for this product.
     */
    public function manipulation(): HasOne
    {
        return $this->hasOne(ProductManipulation::class);
    }

    /**
     * Get the testimonials for this product.
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
     * Get the real sales count (excluding fake sales count).
     */
    public function getRealSalesAttribute(): int
    {
        return (int) $this->orderItems()
            ->whereHas('order', function ($query) {
                $query->whereIn('order_status', [
                    OrderStatus::CONFIRMED->value,
                    OrderStatus::ON_DELIVERY->value,
                    OrderStatus::ARRIVED->value,
                    OrderStatus::DELIVERED->value,
                ]);
            })
            ->sum('quantity');
    }

    /**
     * Get the total sales for this product (real + fake).
     */
    public function getTotalSalesAttribute(): int
    {
        $fakeSales = $this->manipulation?->is_active ? $this->manipulation->fake_sales_count : 0;

        return $this->real_sales + $fakeSales;
    }

    /**
     * Get the average rating for this product.
     */
    public function getAverageRatingAttribute(): float
    {
        $realStats = $this->testimonials()
            ->selectRaw('count(*) as count, sum(CAST(rating AS NUMERIC)) as sum')
            ->groupBy('order_items.product_id')
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
     * @return Collection
     */
    public function getManipulatedTestimonials(int $limit = 10)
    {
        $realTestimonials = $this->testimonials()
            ->with('customer')
            ->latest()
            ->get()
            ->map(fn ($t) => [
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
                ->map(fn ($t, $index) => [
                    'id' => 'fake-'.$t->id.'-'.$index,
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
     */
    protected function getImageAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }
}
