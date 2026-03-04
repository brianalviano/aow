<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DropPoint extends Model
{
    use HasFactory, HasUuids, SoftDeletes, FileHelperTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'category',
        'photo',
        'address',
        'phone',
        'latitude',
        'longitude',
        'pic_name',
        'pic_phone',
        'is_active',
        'delivery_fee',
        'min_po_qty',
        'min_po_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'category' => \App\Enums\DropPointCategory::class,
            'min_po_qty' => 'integer',
            'min_po_amount' => 'integer',
        ];
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
