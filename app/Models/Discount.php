<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Concerns\HasUuids, Factories\HasFactory, Relations\HasMany};

class Discount extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'scope',
        'value_type',
        'value',
        'start_at',
        'end_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DiscountItem::class);
    }
}
