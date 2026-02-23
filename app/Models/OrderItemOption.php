<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a selected option for a specific order item.
 */
class OrderItemOption extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_item_id',
        'product_option_id',
        'product_option_item_id',
        'extra_price',
    ];

    /**
     * Get the order item that this option belongs to.
     *
     * @return BelongsTo
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the product option associated with this order item option.
     *
     * @return BelongsTo
     */
    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }

    /**
     * Get the specific product option item chosen.
     *
     * @return BelongsTo
     */
    public function productOptionItem(): BelongsTo
    {
        return $this->belongsTo(ProductOptionItem::class);
    }
}
