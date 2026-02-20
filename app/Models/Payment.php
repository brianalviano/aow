<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\{PaymentDirection, PaymentStatus};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'direction',
        'party',
        'payment_date',
        'payment_method_id',
        'amount',
        'reference_number',
        'cash_bank_account_id',
        'notes',
        'created_by_id',
        'status',
        'posted_at',
        'voided_at',
        'voided_by_id',
        'void_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'integer',
            'posted_at' => 'datetime: Y-m-d H:i:s',
            'voided_at' => 'datetime: Y-m-d H:i:s',
            'direction' => PaymentDirection::class,
            'status' => PaymentStatus::class,
        ];
    }

    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(CashBankAccount::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
