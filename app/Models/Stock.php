<?php

namespace App\Models;

use App\Enums\{StockType, StockBucket};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'product_division_id',
        'product_rack_id',
        'owner_user_id',
        'quantity',
        'bucket',
        'type',
        'locked_by_stock_opname_id',
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
            'type' => StockType::class,
            'bucket' => StockBucket::class,
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function lockedByStockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }
}
