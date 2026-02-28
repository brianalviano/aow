<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChefOrderType;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Chef mitra yang bekerja sama dengan perusahaan.
 *
 * Setiap chef bisa di-assign ke satu atau lebih produk.
 * Perusahaan mendapat fee (persentase) dari penjualan produk chef.
 * Transfer dana dicatat melalui relasi `transfers`.
 */
class Chef extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'business_name',
        'phone',
        'bank_name',
        'account_number',
        'account_name',
        'note',
        'fee_percentage',
        'address',
        'longitude',
        'latitude',
        'email',
        'password',
        'is_active',
        'order_types',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'fee_percentage' => 'float',
            'latitude'       => 'float',
            'longitude'      => 'float',
            'is_active'      => 'boolean',
            'order_types'    => \Illuminate\Database\Eloquent\Casts\AsEnumArrayObject::class . ':' . ChefOrderType::class,
        ];
    }

    /**
     * Produk-produk yang di-assign ke chef ini.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    /**
     * Riwayat transfer dana ke chef.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(ChefTransfer::class);
    }
}
