<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk data pengiriman per-chef pada suatu order.
 *
 * Setiap record merepresentasikan satu shipment dari satu chef
 * menggunakan kurir instant (Grab/Gojek) via Biteship.
 */
class OrderShipping extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'chef_id',
        'courier_company',
        'courier_type',
        'courier_name',
        'shipping_fee',
        'origin_address',
        'origin_latitude',
        'origin_longitude',
        'destination_latitude',
        'destination_longitude',
        'biteship_order_id',
        'biteship_tracking_id',
        'biteship_waybill_id',
        'biteship_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'origin_latitude' => 'float',
            'origin_longitude' => 'float',
            'destination_latitude' => 'float',
            'destination_longitude' => 'float',
        ];
    }

    /**
     * Order yang memiliki shipping ini.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Chef yang mengirim untuk shipment ini.
     */
    public function chef(): BelongsTo
    {
        return $this->belongsTo(Chef::class);
    }
}
