<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'mdr_percentage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'mdr_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shiftSalesPaymentMethods(): HasMany
    {
        return $this->hasMany(ShiftSalesPaymentMethod::class);
    }
}
