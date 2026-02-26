<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'drop_point_id',
        'customer_id',
        'delivery_date',
        'payment_method_id',
        'barcode',
        'tracking_number',
        'shipping_method',
        'payment_status',
        'order_status',
        'note',
        'cancellation_note',
        'snap_token',
        'payment_url',
        'payment_reference',
        'payment_expired_at',
        'product_discount_id',
        'shipping_discount_id',
        'discount_amount',
        'total_amount',
        'payment_details',
        'delivery_fee',
        'delivery_discount_amount',
        'final_delivery_fee',
        'admin_fee',
        'tax_amount',
        'delivery_time',
        'payment_proof',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'delivery_time' => 'datetime',
            'payment_expired_at' => 'timestamp',
            'payment_details' => 'array',
        ];
    }

    /**
     * Get the items associated with this order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function dropPoint(): BelongsTo
    {
        return $this->belongsTo(DropPoint::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function productDiscount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function shippingDiscount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
