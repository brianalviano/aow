<?php

namespace App\Models;

use App\Enums\{PaymentMethodCategory, PaymentMethodType};
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, HasUuids, SoftDeletes, FileHelperTrait;

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
        'service_fee_rate',
        'service_fee_fixed',
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
            'service_fee_rate' => 'float',
            'service_fee_fixed' => 'integer',
        ];
    }

    /**
     * Get the payment guide linked to this method.
     */
    public function paymentGuide(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentGuide::class);
    }

    /**
     * Get the photo URL.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function getPhotoAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }
}
