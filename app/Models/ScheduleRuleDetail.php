<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleRuleDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'schedule_rule_id',
        'day_of_week',
        'shift_id',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ScheduleRule::class, 'schedule_rule_id', 'id');
    }
}

