<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingMethod;
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use FileHelperTrait, HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
        'arrived_at',
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
            'arrived_at' => 'datetime',
            'payment_expired_at' => 'timestamp',
            'payment_details' => 'array',
            'shipping_method' => ShippingMethod::class,
            'payment_status' => PaymentStatus::class,
            'order_status' => OrderStatus::class,
        ];
    }

    /**
     * Get the items associated with this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function dropPoint(): BelongsTo
    {
        return $this->belongsTo(DropPoint::class);
    }

    /**
     * Determine if this is a pre-order (delivered to drop point).
     */
    public function isPreOrder(): bool
    {
        return $this->drop_point_id !== null || ($this->delivery_date && $this->delivery_date->isAfter(now()->startOfDay()));
    }

    /**
     * Determine if this is an instant order (delivered to custom address via courier).
     */
    public function isInstant(): bool
    {
        return $this->customer_address_id !== null && $this->drop_point_id === null && (! $this->delivery_date || $this->delivery_date->isToday());
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the customer address associated with this order.
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
     * Get the shipping records for this order.
     */
    public function shippings(): HasMany
    {
        return $this->hasMany(OrderShipping::class);
    }

    /**
     * Get the payment proof URL.
     */
    protected function getPaymentProofAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }

    /**
     * Get the delivery photo URL.
     */
    protected function getDeliveryPhotoAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }
}
