<?php

namespace App\Models;

use App\Enums\{SalesReturnReason, SalesReturnResolution, SalesReturnStatus};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturn extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sales_id',
        'warehouse_id',
        'number',
        'return_datetime',
        'reason',
        'resolution',
        'refund_amount',
        'status',
        'created_by_id',
        'canceled_by_id',
        'canceled_at',
        'canceled_reason',
        'notes',
        'is_stock_returned',
        'customer_store_credit_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_datetime' => 'datetime',
            'refund_amount' => 'integer',
            'canceled_at' => 'datetime: Y-m-d H:i:s',
            'is_stock_returned' => 'boolean',
            'reason' => SalesReturnReason::class,
            'resolution' => SalesReturnResolution::class,
            'status' => SalesReturnStatus::class,
        ];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customerStoreCredit(): BelongsTo
    {
        return $this->belongsTo(CustomerStoreCredit::class);
    }
}
