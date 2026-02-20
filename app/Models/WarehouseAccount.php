<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseAccount extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'warehouse_id',
        'inventory_account_id',
        'cogs_account_id',
        'adjustment_increase_account_id',
        'adjustment_decrease_account_id',
        'account_id',
    ];

    // no casts

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function adjustmentIncreaseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function adjustmentDecreaseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
