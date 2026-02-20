<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Concerns\HasUuids, Factories\HasFactory, Relations\BelongsTo, Relations\MorphTo};

class DiscountItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'discount_id',
        'itemable_type',
        'itemable_id',
        'min_qty_buy',
        'is_multiple',
        'free_product_id',
        'free_qty_get',
        'custom_value',
    ];

    protected function casts(): array
    {
        return [
            'min_qty_buy' => 'integer',
            'is_multiple' => 'boolean',
            'free_qty_get' => 'integer',
            'custom_value' => 'decimal:2',
        ];
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function freeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'free_product_id');
    }
}
