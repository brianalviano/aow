<?php

namespace App\Models;

use App\Enums\SupplierBillStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBill extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier_id',
        'purchase_order_id',
        'warehouse_id',
        'number',
        'supplier_invoice_number',
        'bill_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'is_value_added_tax_enabled',
        'value_added_tax_id',
        'value_added_tax_percentage',
        'value_added_tax_amount',
        'grand_total',
        'outstanding_amount',
        'is_income_tax_enabled',
        'income_tax_id',
        'income_tax_percentage',
        'income_tax_amount',
        'net_payable_amount',
        'status',
        'notes',
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
            'bill_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'is_value_added_tax_enabled' => 'boolean',
            'value_added_tax_percentage' => 'decimal:2',
            'value_added_tax_amount' => 'integer',
            'grand_total' => 'integer',
            'outstanding_amount' => 'integer',
            'is_income_tax_enabled' => 'boolean',
            'income_tax_percentage' => 'decimal:2',
            'income_tax_amount' => 'integer',
            'net_payable_amount' => 'integer',
            'status' => SupplierBillStatus::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function valueAddedTax(): BelongsTo
    {
        return $this->belongsTo(ValueAddedTax::class);
    }

    public function incomeTax(): BelongsTo
    {
        return $this->belongsTo(IncomeTax::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
