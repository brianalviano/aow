<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model for testimonial templates used for product data manipulation.
 */
class TestimonialTemplate extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'customer_name',
        'rating',
        'content',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
