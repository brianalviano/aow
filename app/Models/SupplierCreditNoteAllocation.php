<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierCreditNoteAllocation extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier_credit_note_id',
        'supplier_bill_id',
        'amount',
        'allocated_at',
        'allocated_by_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'allocated_at' => 'datetime: Y-m-d H:i:s',
        ];
    }

    public function supplierCreditNote(): BelongsTo
    {
        return $this->belongsTo(SupplierCreditNote::class);
    }

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
