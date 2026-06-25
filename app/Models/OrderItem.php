<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChefStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'discount_id',
        'quantity',
        'price',
        'cost_price',
        'discount_amount',
        'final_price',
        'subtotal',
        'note',
        'chef_id',
        'chef_status',
        'chef_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'chef_status' => ChefStatus::class,
            'chef_confirmed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function chef(): BelongsTo
    {
        return $this->belongsTo(Chef::class);
    }

    /**
     * Get the options selected for this order item.
     */
    public function options(): HasMany
    {
        return $this->hasMany(OrderItemOption::class);
    }

    /**
     * Get the testimonial for this order item.
     */
    public function testimonial(): HasOne
    {
        return $this->hasOne(Testimonial::class);
    }

    /**
     * Determine if the customer can give a testimonial for this item.
     */
    public function canBeTestimonialed(): bool
    {
        $order = $this->order;

        if ($order->order_status !== OrderStatus::DELIVERED || ! $order->delivered_at) {
            return false;
        }

        if ($this->relationLoaded('testimonial') ? $this->testimonial !== null : $this->testimonial()->exists()) {
            return false;
        }

        return true;
    }
}
