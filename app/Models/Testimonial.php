<?php

namespace App\Models;

use App\Enums\TestimonialRating;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use HasUuids, HasFactory, FileHelperTrait;

    protected $fillable = [
        'customer_id',
        'order_id',
        'rating',
        'content',
        'photo',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'rating' => TestimonialRating::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
