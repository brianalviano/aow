<?php

namespace App\Models;

use App\Enums\{PurchaseReturnReason, PurchaseReturnResolution, PurchaseReturnStatus};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'number',
        'return_date',
        'reason',
        'resolution',
        'status',
        'resolved_at',
        'resolved_by_id',
        'credit_amount',
        'refund_amount',
        'notes',
        'is_processed_by_finance',
        'created_by_id',
        'canceled_by_id',
        'canceled_at',
        'canceled_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'resolved_at' => 'datetime: Y-m-d H:i:s',
            'credit_amount' => 'integer',
            'refund_amount' => 'integer',
            'is_processed_by_finance' => 'boolean',
            'canceled_at' => 'datetime: Y-m-d H:i:s',
            'reason' => PurchaseReturnReason::class,
            'resolution' => PurchaseReturnResolution::class,
            'status' => PurchaseReturnStatus::class,
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
