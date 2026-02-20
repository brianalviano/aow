<?php

namespace App\Models;

use App\Enums\JournalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'journal_date',
        'description',
        'warehouse_id',
        'source_referencable',
        'status',
        'total_debit',
        'total_credit',
        'posted_at',
        'posted_by_id',
        'canceled_by_id',
        'canceled_at',
        'canceled_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'total_debit' => 'integer',
            'total_credit' => 'integer',
            'posted_at' => 'datetime: Y-m-d H:i:s',
            'canceled_at' => 'datetime: Y-m-d H:i:s',
            'status' => JournalStatus::class,
        ];
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
