<?php

namespace App\Models;

use App\Enums\{StockAdjustmentReason, StockAdjustmentStatus};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'warehouse_id',
        'reason',
        'status',
        'approved_by_id',
        'approved_via_pin',
        'supervisor_approval_id',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_via_pin' => 'boolean',
            'reason' => StockAdjustmentReason::class,
            'status' => StockAdjustmentStatus::class,
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supervisorApproval(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
