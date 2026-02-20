<?php

declare(strict_types=1);

namespace App\DTOs\StockDocument;

/**
 * Payload item surat stok manual.
 *
 * @param string      $productId
 * @param int         $quantity    Wajib > 0
 * @param int|null    $unitPrice   Harga satuan (IDR).
 * @param string|null $notes
 * @param string|null $productDivisionId
 * @param string|null $productRackId
 * @param string|null $ownerUserId
 *
 * @throws \InvalidArgumentException Jika quantity <= 0 atau unitPrice < 0.
 */
readonly class StockDocumentItemData
{
    public function __construct(
        public string  $productId,
        public int     $quantity,
        public ?int    $unitPrice = null,
        public ?string $notes = null,
        public ?string $productDivisionId = null,
        public ?string $productRackId = null,
        public ?string $ownerUserId = null,
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity harus > 0');
        }
        if ($unitPrice !== null && $unitPrice < 0) {
            throw new \InvalidArgumentException('Unit price tidak boleh negatif');
        }
    }
}

