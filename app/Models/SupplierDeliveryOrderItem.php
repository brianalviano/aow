<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Supplier Delivery Order Item.
 *
 * Baris item dalam dokumen SupplierDeliveryOrder yang mencatat produk dan kuantitas
 * yang direncanakan/dikirim oleh pemasok untuk diterima sebagai Goods Receipt.
 *
 * @property string $id
 * @property string $supplier_delivery_order_id
 * @property string $product_id
 * @property int $quantity
 * @property string|null $notes
 */

class SupplierDeliveryOrderItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier_delivery_order_id',
        'product_id',
        'quantity',
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
        ];
    }

    public function supplierDeliveryOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierDeliveryOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
