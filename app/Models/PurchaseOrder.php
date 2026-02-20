<?php

namespace App\Models;

use App\Enums\{PurchaseOrderSupplierSource, PurchaseOrderStatus};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\{SupplierDeliveryOrder, PurchaseReturn, SupplierBill};

class PurchaseOrder extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'supplier_source',
        'supplier_id',
        'warehouse_id',
        'created_by_id',
        'is_processed_by_finance',
        'notes',
        'order_date',
        'due_date',
        'status',
        'director_id',
        'director_decision_at',
        'supplier_decision_at',
        'rejected_manager_reason',
        'rejected_director_reason',
        'rejected_supplier_reason',
        'canceled_by_id',
        'canceled_at',
        'canceled_reason',
        'payment_date',
        'subtotal',
        'delivery_cost',
        'total_before_discount',
        'discount_percentage',
        'discount_amount',
        'total_after_discount',
        'value_added_tax_included',
        'is_value_added_tax_enabled',
        'value_added_tax_id',
        'value_added_tax_percentage',
        'value_added_tax_amount',
        'total_after_value_added_tax',
        'is_income_tax_enabled',
        'income_tax_id',
        'income_tax_percentage',
        'income_tax_amount',
        'total_after_income_tax',
        'grand_total',
        'supplier_invoice_number',
        'supplier_invoice_file',
        'supplier_invoice_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_processed_by_finance' => 'boolean',
            'order_date' => 'date',
            'due_date' => 'date',
            'director_decision_at' => 'datetime: Y-m-d H:i:s',
            'supplier_decision_at' => 'datetime: Y-m-d H:i:s',
            'canceled_at' => 'datetime: Y-m-d H:i:s',
            'payment_date' => 'date',
            'subtotal' => 'integer',
            'delivery_cost' => 'integer',
            'total_before_discount' => 'integer',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'integer',
            'total_after_discount' => 'integer',
            'value_added_tax_included' => 'boolean',
            'is_value_added_tax_enabled' => 'boolean',
            'value_added_tax_percentage' => 'decimal:2',
            'value_added_tax_amount' => 'integer',
            'total_after_value_added_tax' => 'integer',
            'is_income_tax_enabled' => 'boolean',
            'income_tax_percentage' => 'decimal:2',
            'income_tax_amount' => 'integer',
            'total_after_income_tax' => 'integer',
            'grand_total' => 'integer',
            'supplier_invoice_date' => 'date',
            'status' => PurchaseOrderStatus::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function valueAddedTax(): BelongsTo
    {
        return $this->belongsTo(ValueAddedTax::class);
    }

    public function incomeTax(): BelongsTo
    {
        return $this->belongsTo(IncomeTax::class);
    }

    /**
     * Get all of the items for the PurchaseOrder
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }

    public function supplierDeliveryOrders(): MorphMany
    {
        return $this->morphMany(SupplierDeliveryOrder::class, 'sourceable');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function supplierBills(): HasMany
    {
        return $this->hasMany(SupplierBill::class);
    }
}
