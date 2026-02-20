<?php

namespace App\Models;

use App\Enums\SupplierDeliveryOrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphTo};

/**
 * Supplier Delivery Order.
 *
 * Dokumen pengiriman pemasok yang menjadi sumber penerimaan barang (Goods Receipt).
 * Mendukung sumber polymorphic (sourceable) sehingga dapat merujuk ke PurchaseOrder
 * maupun PurchaseReturn untuk kasus penggantian barang.
 *
 * @property string $id
 * @property string $supplier_id
 * @property \Illuminate\Support\Carbon|null $delivery_date
 * @property string|null $number
 * @property \App\Enums\SupplierDeliveryOrderStatus|string $status
 * @property string|null $notes
 * @property string|null $sourceable_id
 * @property string|null $sourceable_type
 */
class SupplierDeliveryOrder extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'delivery_date',
        'number',
        'status',
        'notes',
        'sourceable_id',
        'sourceable_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'status' => SupplierDeliveryOrderStatus::class,
        ];
    }

    /**
     * @return BelongsTo
     */
    // purchaseOrder() dihapus; gunakan sourceable untuk merujuk ke PO/PR

    /**
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(SupplierDeliveryOrderItem::class);
    }

    /**
     * Polymorphic source of the delivery order (PurchaseOrder or PurchaseReturn).
     *
     * @return MorphTo
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'sourceable_type', 'sourceable_id');
    }
}
