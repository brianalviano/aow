<?php

namespace App\Models;

use App\Enums\SupplierCreditNoteStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierCreditNote extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier_id',
        'purchase_return_id',
        'number',
        'credit_date',
        'amount',
        'remaining_amount',
        'status',
        'notes',
        'created_by_id',
        'posted_at',
        'posted_by_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credit_date' => 'date',
            'amount' => 'integer',
            'remaining_amount' => 'integer',
            'posted_at' => 'datetime: Y-m-d H:i:s',
            'status' => SupplierCreditNoteStatus::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
