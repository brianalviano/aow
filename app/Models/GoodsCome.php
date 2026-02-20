<?php

namespace App\Models;

use App\Enums\GoodsComeSourceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsCome extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'referencable',
        'source_type',
        'warehouse_id',
        'product_division_id',
        'product_rack_id',
        'product_id',
        'quantity',
        'quantity_return',
        'unit_cost',
        'notes',
        'expired_date',
        'previous_stock',
        'stock_after',
        'batch_numbers',
        'barcode',
        'supplier_name',
        'sender_name',
        'vehicle_plate_number',
        'invoice_number',
        'purchase_date',
        'created_by_id',
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
            'quantity_return' => 'integer',
            'unit_cost' => 'integer',
            'expired_date' => 'date',
            'previous_stock' => 'integer',
            'stock_after' => 'integer',
            'purchase_date' => 'date',
            'source_type' => GoodsComeSourceType::class,
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productDivision(): BelongsTo
    {
        return $this->belongsTo(ProductDivision::class);
    }

    public function productRack(): BelongsTo
    {
        return $this->belongsTo(ProductRack::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
