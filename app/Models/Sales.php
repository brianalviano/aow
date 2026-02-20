<?php

namespace App\Models;

use App\Enums\{SalesDeliveryType, SalesStatus, SalesPaymentStatus};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sales extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'warehouse_id',
        'cashier_session_id',
        'receipt_number',
        'invoice_number',
        'sale_datetime',
        'customer_id',
        'delivery_type',
        'requires_delivery',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'discount',
        'item_discount_total',
        'extra_discount_total',
        'voucher_code',
        'voucher_amount',
        'total_after_discount',
        'is_value_added_tax_enabled',
        'value_added_tax_percentage',
        'value_added_tax_amount',
        'value_added_tax_id',
        'grand_total',
        'status',
        'created_by_id',
        'canceled_by_id',
        'canceled_at',
        'canceled_reason',
        'canceled_via_pin',
        'cancel_approval_id',
        'notes',
        'payment_status',
        'outstanding_amount',
        'change_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_datetime' => 'datetime',
            'requires_delivery' => 'boolean',
            'subtotal' => 'integer',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'integer',
            'discount' => 'integer',
            'item_discount_total' => 'integer',
            'extra_discount_total' => 'integer',
            'voucher_amount' => 'integer',
            'total_after_discount' => 'integer',
            'is_value_added_tax_enabled' => 'boolean',
            'value_added_tax_percentage' => 'decimal:2',
            'value_added_tax_amount' => 'integer',
            'grand_total' => 'integer',
            'canceled_at' => 'datetime: Y-m-d H:i:s',
            'canceled_via_pin' => 'boolean',
            'outstanding_amount' => 'integer',
            'change_amount' => 'integer',
            'delivery_type' => SalesDeliveryType::class,
            'status' => SalesStatus::class,
            'payment_status' => SalesPaymentStatus::class,
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashierSession(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class, 'cashier_session_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function valueAddedTax(): BelongsTo
    {
        return $this->belongsTo(ValueAddedTax::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelApproval(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
