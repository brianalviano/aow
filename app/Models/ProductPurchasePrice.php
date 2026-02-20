<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Concerns\HasUuids, Factories\HasFactory, Relations\BelongsTo};

class ProductPurchasePrice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
