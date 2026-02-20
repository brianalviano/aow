<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Concerns\HasUuids, Factories\HasFactory, Relations\HasMany};

class SellingPriceLevel extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'percent_adjust',
    ];

    public function productSellingPrices(): HasMany
    {
        return $this->hasMany(ProductSellingPrice::class);
    }
}
