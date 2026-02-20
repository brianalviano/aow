<?php

declare(strict_types=1);

namespace App\DTOs\StockTransfer;

use App\Http\Requests\StockTransfer\StoreStockTransferRequest;
use App\Http\Requests\StockTransfer\UpdateStockTransferRequest;

/**
 * Head data for Stock Transfer including items.
 *
 * @author PJD
 *
 * @param string $fromWarehouseId Gudang asal.
 * @param string $toWarehouseId Gudang tujuan.
 * @param string|null $toOwnerUserId Opsional: pemilik stok tujuan (Marketing).
 * @param string $transferDate Tanggal mutasi.
 * @param string|null $status Status mutasi; default draft bila null.
 * @param string|null $notes Catatan tambahan.
 * @param string $createdById ID pengguna yang membuat/mengeksekusi aksi.
 * @param array<StockTransferItemData> $items Daftar item mutasi.
 */
class StockTransferData
{
    public function __construct(
        public string $fromWarehouseId,
        public string $toWarehouseId,
        public ?string $toOwnerUserId,
        public string $transferDate,
        public ?string $status,
        public ?string $notes,
        public string $createdById,
        public array $items,
    ) {}

    public static function fromStoreRequest(StoreStockTransferRequest $request, string $createdById): self
    {
        $p = $request->validated();
        return new self(
            fromWarehouseId: (string) $p['from_warehouse_id'],
            toWarehouseId: (string) $p['to_warehouse_id'],
            toOwnerUserId: $p['to_owner_user_id'] ?? null,
            transferDate: (string) $p['transfer_date'],
            status: $p['status'] ?? null,
            notes: $p['notes'] ?? null,
            createdById: $createdById,
            items: array_map(function ($i) {
                return new StockTransferItemData(
                    productId: (string) $i['product_id'],
                    quantity: (int) $i['quantity'],
                    fromOwnerUserId: $i['from_owner_user_id'] ?? null,
                    toOwnerUserId: $i['to_owner_user_id'] ?? null,
                );
            }, (array) ($p['items'] ?? [])),
        );
    }

    public static function fromUpdateRequest(UpdateStockTransferRequest $request, string $createdById): self
    {
        $p = $request->validated();
        return new self(
            fromWarehouseId: (string) $p['from_warehouse_id'],
            toWarehouseId: (string) $p['to_warehouse_id'],
            toOwnerUserId: $p['to_owner_user_id'] ?? null,
            transferDate: (string) $p['transfer_date'],
            status: $p['status'] ?? null,
            notes: $p['notes'] ?? null,
            createdById: $createdById,
            items: array_map(function ($i) {
                return new StockTransferItemData(
                    productId: (string) $i['product_id'],
                    quantity: (int) $i['quantity'],
                    fromOwnerUserId: $i['from_owner_user_id'] ?? null,
                    toOwnerUserId: $i['to_owner_user_id'] ?? null,
                );
            }, (array) ($p['items'] ?? [])),
        );
    }
}

