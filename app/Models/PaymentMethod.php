<?php

namespace App\Models;

use App\Enums\{PaymentMethodCategory, PaymentMethodType};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'category',
        'type',
        'code',
        'photo',
        'is_active',
        'account_number',
        'account_name',
        'payment_guide_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'category' => PaymentMethodCategory::class,
            'type' => PaymentMethodType::class,
        ];
    }

    /**
     * Get the payment guide linked to this method.
     */
    public function paymentGuide(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentGuide::class);
    }
}
