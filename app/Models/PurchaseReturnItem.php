<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_return_id',
        'goods_come_id',
        'product_id',
        'product_division_id',
        'product_rack_id',
        'quantity',
        'price',
        'subtotal',
        'expired_date',
        'batch_numbers',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'integer',
            'subtotal' => 'integer',
            'expired_date' => 'date',
        ];
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function goodsCome(): BelongsTo
    {
        return $this->belongsTo(GoodsCome::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productDivision(): BelongsTo
    {
        return $this->belongsTo(ProductDivision::class);
    }

    public function productRack(): BelongsTo
    {
        return $this->belongsTo(ProductRack::class);
    }
}
