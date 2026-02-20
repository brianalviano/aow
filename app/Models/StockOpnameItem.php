<?php

namespace App\Models;

use App\Enums\StockOpnameItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'stock_opname_id',
        'stock_opname_assignment_id',
        'product_id',
        'system_quantity',
        'actual_quantity',
        'difference',
        'hpp',
        'subtotal',
        'notes',
        'status',
        'counted_at',
        'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_quantity' => 'integer',
            'actual_quantity' => 'integer',
            'difference' => 'integer',
            'hpp' => 'integer',
            'subtotal' => 'integer',
            'counted_at' => 'datetime',
            'verified_at' => 'datetime',
            'status' => StockOpnameItemStatus::class,
        ];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function stockOpnameAssignment(): BelongsTo
    {
        return $this->belongsTo(StockOpnameAssignment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
