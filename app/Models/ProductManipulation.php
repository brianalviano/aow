<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for product-specific data manipulation settings.
 */
class ProductManipulation extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'fake_sales_count',
        'fake_testimonials_count',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fake_sales_count' => 'integer',
            'fake_testimonials_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the product associated with this manipulation setting.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
