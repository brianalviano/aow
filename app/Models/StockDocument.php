<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\{StockDocumentType, StockDocumentReason, StockBucket, StockDocumentStatus};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphTo};

class StockDocument extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'number',
        'document_date',
        'type',
        'reason',
        'status',
        'bucket',
        'warehouse_id',
        'user_id',
        'sourceable_id',
        'sourceable_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'type'          => StockDocumentType::class,
            'reason'        => StockDocumentReason::class,
            'status'        => StockDocumentStatus::class,
            'bucket'        => StockBucket::class,
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(StockDocumentItem::class);
    }
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }
}
