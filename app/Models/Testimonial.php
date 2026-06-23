<?php

namespace App\Models;

use App\Enums\TestimonialRating;
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use FileHelperTrait, HasFactory, HasUuids;

    protected $fillable = [
        'customer_id',
        'order_item_id',
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

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the photo URL.
     */
    protected function getPhotoAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }
}
