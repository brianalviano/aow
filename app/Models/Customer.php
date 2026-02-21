<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Customer model – represents a registered customer who can authenticate
 * via the `customer` guard (session-based).
 *
 * @property string $id UUID primary key
 * @property string $drop_point_id
 * @property string $name
 * @property string $username
 * @property string $phone
 * @property string $address
 * @property string $email
 * @property string $password  (hashed)
 * @property string|null $school_class
 * @property bool   $is_active
 */
class Customer extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'drop_point_id',
        'name',
        'username',
        'phone',
        'address',
        'email',
        'password',
        'school_class',
        'is_active',
        'remember_token',
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
            'is_active' => 'boolean',
            'password'  => 'hashed',
        ];
    }

    public function dropPoint(): BelongsTo
    {
        return $this->belongsTo(DropPoint::class);
    }
}
