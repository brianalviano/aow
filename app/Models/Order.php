<?php

namespace App\Models;

use App\Enums\{OrderStatus, PaymentStatus, ShippingMethod};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes, FileHelperTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'drop_point_id',
        'customer_address_id',
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
        'service_fee',
        'tax_amount',
        'delivery_time',
        'delivered_at',
        'payment_proof',
        'delivery_photo',
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
            'delivered_at' => 'datetime',
            'payment_expired_at' => 'timestamp',
            'payment_details' => 'array',
            'shipping_method' => ShippingMethod::class,
            'payment_status' => PaymentStatus::class,
            'order_status' => OrderStatus::class,
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

    /**
     * Get the customer address associated with this order.
     *
     * @return BelongsTo
     */
    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class);
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



    /**
     * Get the payment proof URL.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function getPaymentProofAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }

    /**
     * Get the delivery photo URL.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function getDeliveryPhotoAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }

    /**
     * Get aggregate chef status for the order.
     * 
     * @return string (pending|accepted|rejected|partial)
     */
    public function getChefStatusSummaryAttribute(): string
    {
        if (!$this->items->count()) {
            return 'pending';
        }

        $statuses = $this->items->pluck('chef_status')->unique();

        if ($statuses->contains(\App\Enums\ChefStatus::REJECTED)) {
            return 'rejected';
        }

        if ($statuses->contains(\App\Enums\ChefStatus::PENDING) || $statuses->contains(null)) {
            $hasProcessed = $statuses->contains(\App\Enums\ChefStatus::ACCEPTED)
                || $statuses->contains(\App\Enums\ChefStatus::SHIPPED)
                || $statuses->contains(\App\Enums\ChefStatus::DELIVERED);

            return $hasProcessed ? 'partial' : 'pending';
        }

        return 'accepted';
    }
}
