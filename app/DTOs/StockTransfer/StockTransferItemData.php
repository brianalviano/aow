<?php

declare(strict_types=1);

namespace App\DTOs\StockTransfer;

/**
 * Item data for Stock Transfer.
 *
 * @author PJD
 *
 * @param string $productId ID produk yang dipindahkan.
 * @param int $quantity Jumlah unit yang dipindahkan.
 * @param string|null $fromOwnerUserId Opsional: pemilik stok asal (Marketing) bila stok berasal dari marketing tertentu.
 * @param string|null $toOwnerUserId Opsional: pemilik stok tujuan (Marketing) untuk pengalokasian di gudang tujuan.
 */
class StockTransferItemData
{
    public function __construct(
        public string $productId,
        public int $quantity,
        public ?string $fromOwnerUserId,
        public ?string $toOwnerUserId,
    ) {}
}
