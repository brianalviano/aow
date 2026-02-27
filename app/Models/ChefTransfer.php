<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\FileHelperTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pencatatan transfer dana ke chef mitra.
 *
 * Setiap record merepresentasikan satu kali transfer yang sudah terjadi.
 * Field `fee_percentage` dan `fee_amount` adalah snapshot saat transfer
 * untuk keperluan audit trail.
 */
class ChefTransfer extends Model
{
    use HasFactory, HasUuids, FileHelperTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'chef_id',
        'amount',
        'fee_percentage',
        'fee_amount',
        'gross_amount',
        'note',
        'transfer_proof',
        'transferred_at',
    ];

    /**
     * Attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee_percentage'  => 'float',
            'transferred_at'  => 'datetime',
        ];
    }

    /**
     * Chef pemilik transfer ini.
     */
    public function chef(): BelongsTo
    {
        return $this->belongsTo(Chef::class);
    }

    /**
     * Get the transfer proof file URL.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function getTransferProofAttribute(?string $value): ?string
    {
        return $this->getFileUrl($value);
    }
}
