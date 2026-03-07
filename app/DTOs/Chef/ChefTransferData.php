<?php

declare(strict_types=1);

namespace App\DTOs\Chef;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for ChefTransfer create operations.
 *
 * Transports validated transfer data from HTTP request to Service layer.
 * Fee calculations are done in the Service based on the Chef's current fee_percentage.
 *
 * @property int $grossAmount Jumlah kotor sebelum potong fee (IDR)
 * @property string|null $note Catatan transfer
 * @property UploadedFile|null $transferProof Bukti transfer (file gambar)
 * @property string $transferredAt Tanggal transfer (Y-m-d atau datetime)
 */
class ChefTransferData extends Data
{
    public function __construct(
        #[Rule('required', 'integer', 'min:1')]
        public readonly int $grossAmount,

        #[Rule('nullable', 'string')]
        public readonly ?string $note,

        #[Rule('nullable', 'image', 'max:2048')]
        public readonly ?UploadedFile $transferProof = null,

        #[Rule('required', 'date')]
        public readonly string $transferredAt = '',
    ) {}
}
