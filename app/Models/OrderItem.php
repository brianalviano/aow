<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'discount_amount',
        'final_price',
        'subtotal',
        'note',
    ];

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

    /**
     * Get the options selected for this order item.
     *
     * @return HasMany
     */
    public function options(): HasMany
    {
        return $this->hasMany(OrderItemOption::class);
    }

    /**
     * Get the testimonial for this order item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function testimonial(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Testimonial::class);
    }

    /**
     * Determine if the customer can give a testimonial for this item.
     *
     * @return bool
     */
    public function canBeTestimonialed(): bool
    {
        $order = $this->order;

        if ($order->order_status !== \App\Enums\OrderStatus::DELIVERED || !$order->delivered_at) {
            return false;
        }

        if ($this->testimonial()->exists()) {
            return false;
        }

        return true;
    }
}
