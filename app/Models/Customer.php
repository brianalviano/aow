<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomerSource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'latitude',
        'longitude',
        'is_active',
        'source',
        'created_by_id',
        'is_visible_in_pos',
        'is_visible_in_marketing',
        'last_transaction_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'source' => CustomerSource::class,
            'is_visible_in_pos' => 'boolean',
            'is_visible_in_marketing' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'last_transaction_at' => 'datetime: Y-m-d H:i:s',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function marketers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_marketers');
    }
}
