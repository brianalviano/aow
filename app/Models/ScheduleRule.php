<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'is_active',
        'rotation_even_shift_id',
        'rotation_odd_shift_id',
        'rotation_off_day',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'rotation_off_day' => 'integer',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(ScheduleRuleDetail::class);
    }
}
