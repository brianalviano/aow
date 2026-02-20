<?php

namespace App\Models;

use App\Enums\AverageCostTransactionType;
use App\Enums\StockBucket;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AverageCostHistory extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'average_cost_id',
        'bucket',
        'cost',
        'quantity_affected',
        'transaction_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'quantity_affected' => 'integer',
            'transaction_type' => AverageCostTransactionType::class,
            'bucket' => StockBucket::class,
        ];
    }

    public function averageCost(): BelongsTo
    {
        return $this->belongsTo(AverageCost::class);
    }
}
