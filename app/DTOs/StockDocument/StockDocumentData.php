<?php

declare(strict_types=1);

namespace App\DTOs\StockDocument;

use App\Enums\{StockDocumentType, StockDocumentReason, StockBucket, StockDocumentStatus};

/**
 * Payload header surat stok manual.
 *
 * @param string              $warehouseId  Gudang tujuan.
 * @param StockDocumentType   $type         Jenis dokumen: in|out.
 * @param StockDocumentReason $reason       Alasan dokumen.
 * @param string              $userId       ID pembuat dokumen.
 * @param string|null         $documentDate Tanggal dokumen (Y-m-d).
 * @param string|null         $number       Nomor dokumen; null = auto.
 * @param string|null         $sourceableType Class sumber.
 * @param string|null         $sourceableId ID sumber.
 * @param StockBucket|null    $bucket       Bucket stok: vat/non_vat.
 * @param string|null         $notes        Catatan dokumen.
 */
readonly class StockDocumentData
{
    public function __construct(
        public string            $warehouseId,
        public StockDocumentType $type,
        public StockDocumentReason $reason,
        public string            $userId,
        public ?string           $documentDate = null,
        public ?string           $number = null,
        public ?string           $sourceableType = null,
        public ?string           $sourceableId = null,
        public ?StockBucket      $bucket = null,
        public ?string           $notes = null,
        public ?StockDocumentStatus $status = null,
    ) {}
}
