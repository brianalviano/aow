<?php

declare(strict_types=1);

namespace App\DTOs\Chef;

use App\Http\Requests\Admin\Chef\StoreChefTransferRequest;
use Illuminate\Http\UploadedFile;

/**
 * Data Transfer Object for ChefTransfer create operations.
 *
 * Transports validated transfer data from Form Request to Service layer.
 * Fee calculations are done in the Service based on the Chef's current fee_percentage.
 */
class ChefTransferData
{
    /**
     * @param int $grossAmount Jumlah kotor sebelum potong fee (IDR)
     * @param string|null $note Catatan transfer
     * @param UploadedFile|null $transferProof Bukti transfer (file gambar)
     * @param string $transferredAt Tanggal transfer (Y-m-d atau datetime)
     */
    public function __construct(
        public readonly int $grossAmount,
        public readonly ?string $note,
        public readonly ?UploadedFile $transferProof,
        public readonly string $transferredAt,
    ) {}

    /**
     * Create DTO from Store Form Request.
     */
    public static function fromRequest(StoreChefTransferRequest $request): self
    {
        return new self(
            grossAmount: (int) $request->validated('gross_amount'),
            note: $request->validated('note') === null ? null : (string) $request->validated('note'),
            transferProof: $request->file('transfer_proof'),
            transferredAt: (string) $request->validated('transferred_at'),
        );
    }
}
