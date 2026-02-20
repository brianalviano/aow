<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PurchaseOrder\{PurchaseOrderData, PurchaseOrderItemData};
use App\Models\{PurchaseOrder, PurchaseOrderItem, ProductSupplier, SupplierDeliveryOrder, User};
use Illuminate\Support\Facades\{DB, Notification, Log};
use App\Traits\RetryableTransactionsTrait;
use App\Enums\{PurchaseOrderStatus, SupplierDeliveryOrderStatus, RoleName};
use App\Notifications\PurchaseOrderPendingHoNotification;

class PurchaseOrderService
{
    use RetryableTransactionsTrait;

    public function advanceStatus(PurchaseOrder $po): PurchaseOrder
    {
        return $this->runWithRetry(function () use ($po) {
            $updated = DB::transaction(function () use ($po) {
                $current = $po->status instanceof PurchaseOrderStatus ? $po->status : PurchaseOrderStatus::Draft;
                $next = $this->computeNextStatus($current);
                if ($next === null) {
                    throw new \InvalidArgumentException('Status saat ini tidak dapat dilanjutkan');
                }
                $prev = $po->status;
                $po->status = $next->value;
                $po->save();
                if ($prev !== PurchaseOrderStatus::InDelivery->value && $next === PurchaseOrderStatus::InDelivery) {
                    $this->ensureSupplierDeliveryOrder($po);
                }
                return $po;
            }, 5);

            if (($updated->status instanceof PurchaseOrderStatus
                ? $updated->status
                : PurchaseOrderStatus::from((string) $updated->status)
            ) === PurchaseOrderStatus::PendingHoApproval) {
                $this->notifyHoTeamForPendingApproval($updated);
            }

            return $updated;
        }, 3);
    }

    public function setStatus(PurchaseOrder $po, PurchaseOrderStatus $newStatus): PurchaseOrder
    {
        return $this->runWithRetry(function () use ($po, $newStatus) {
            $updated = DB::transaction(function () use ($po, $newStatus) {
                $prev = $po->status;
                $po->status = $newStatus->value;
                $po->save();
                if ($prev !== PurchaseOrderStatus::InDelivery->value && $newStatus === PurchaseOrderStatus::InDelivery) {
                    $this->ensureSupplierDeliveryOrder($po);
                }
                return $po;
            }, 5);

            if ($newStatus === PurchaseOrderStatus::PendingHoApproval) {
                $this->notifyHoTeamForPendingApproval($updated);
            }

            return $updated;
        }, 3);
    }

    public function computeNextStatus(PurchaseOrderStatus $current): ?PurchaseOrderStatus
    {
        return match ($current) {
            PurchaseOrderStatus::Draft => PurchaseOrderStatus::PendingHoApproval,
            PurchaseOrderStatus::PendingHoApproval => PurchaseOrderStatus::HeadOfficeApproved,
            PurchaseOrderStatus::HeadOfficeApproved => PurchaseOrderStatus::PendingSupplierApproval,
            PurchaseOrderStatus::PendingSupplierApproval => PurchaseOrderStatus::SupplierConfirmed,
            PurchaseOrderStatus::SupplierConfirmed => PurchaseOrderStatus::InDelivery,
            PurchaseOrderStatus::InDelivery => null,
            PurchaseOrderStatus::PartiallyDelivered => null,
            PurchaseOrderStatus::Completed => null,
            PurchaseOrderStatus::RejectedByHo => null,
            PurchaseOrderStatus::RejectedBySupplier => null,
            PurchaseOrderStatus::Canceled => null,
        };
    }

    /**
     * @param PurchaseOrder $po
     * @param string $reason
     * @param string $directorId
     * @return PurchaseOrder
     */
    public function rejectByHo(PurchaseOrder $po, string $reason, string $directorId): PurchaseOrder
    {
        return $this->runWithRetry(function () use ($po, $reason, $directorId) {
            return DB::transaction(function () use ($po, $reason, $directorId) {
                $current = $po->status instanceof PurchaseOrderStatus ? $po->status : PurchaseOrderStatus::Draft;
                if ($current !== PurchaseOrderStatus::PendingHoApproval) {
                    throw new \InvalidArgumentException('Penolakan HO hanya diperbolehkan dari status Pending HO Approval');
                }
                $po->status = PurchaseOrderStatus::RejectedByHo->value;
                $po->rejected_director_reason = $reason;
                $po->director_id = $directorId;
                $po->director_decision_at = now();
                $po->save();
                return $po;
            }, 5);
        }, 3);
    }

    /**
     * Kirim notifikasi ke tim HO ketika PO masuk Pending HO Approval.
     *
     * @param PurchaseOrder $po
     * @return void
     */
    private function notifyHoTeamForPendingApproval(PurchaseOrder $po): void
    {
        try {
            $users = User::query()
                ->with(['role'])
                ->whereHas('role', function ($q) {
                    $q->whereIn('name', RoleName::highest());
                })
                ->get();
            if ($users->isNotEmpty()) {
                Notification::send($users, new PurchaseOrderPendingHoNotification($po));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('po_notify_ho_error', [
                'purchase_order_id' => (string) $po->getKey(),
                'number' => (string) ($po->number ?? ''),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * @param PurchaseOrder $po
     * @param string $reason
     * @return PurchaseOrder
     */
    public function rejectBySupplier(PurchaseOrder $po, string $reason): PurchaseOrder
    {
        return $this->runWithRetry(function () use ($po, $reason) {
            return DB::transaction(function () use ($po, $reason) {
                $current = $po->status instanceof PurchaseOrderStatus ? $po->status : PurchaseOrderStatus::Draft;
                if ($current !== PurchaseOrderStatus::PendingSupplierApproval) {
                    throw new \InvalidArgumentException('Penolakan pemasok hanya diperbolehkan dari status Pending Supplier Approval');
                }
                $po->status = PurchaseOrderStatus::RejectedBySupplier->value;
                $po->rejected_supplier_reason = $reason;
                $po->supplier_decision_at = now();
                $po->save();
                return $po;
            }, 5);
        }, 3);
    }

    public function create(PurchaseOrderData $data): PurchaseOrder
    {
        return $this->runWithRetry(function () use ($data) {
            $created = DB::transaction(function () use ($data) {
                $po = new PurchaseOrder();
                $this->fillHead($po, $data);
                $po->number = $this->generateNumber($po);
                $po->created_by_id = $data->createdById;
                $po->status = $data->status ?: PurchaseOrderStatus::Draft->value;
                $po->save();

                $this->syncItems($po, $data->items);
                $this->recalculateTotals($po);
                $po->save();

                return $po;
            }, 5);

            $statusEnum = $created->status instanceof PurchaseOrderStatus
                ? $created->status
                : PurchaseOrderStatus::from((string) $created->status);
            if ($statusEnum === PurchaseOrderStatus::PendingHoApproval) {
                $this->notifyHoTeamForPendingApproval($created);
            }

            return $created;
        }, 3);
    }

    public function update(PurchaseOrder $po, PurchaseOrderData $data): PurchaseOrder
    {
        return $this->runWithRetry(function () use ($po, $data) {
            $updated = DB::transaction(function () use ($po, $data) {
                $current = $po->status instanceof PurchaseOrderStatus
                    ? $po->status
                    : PurchaseOrderStatus::from((string) $po->status);
                if (!in_array($current, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::RejectedByHo, PurchaseOrderStatus::RejectedBySupplier], true)) {
                    throw new \InvalidArgumentException('Edit Purchase Order hanya diperbolehkan pada status Draft, Ditolak HO, atau Ditolak Pemasok');
                }
                $prevStatus = $po->status;
                $this->fillHead($po, $data);
                $po->status = $data->status ?: $po->status;
                $po->save();

                $this->syncItems($po, $data->items);
                $this->recalculateTotals($po);
                $po->save();

                if ($prevStatus !== PurchaseOrderStatus::InDelivery->value && $po->status === PurchaseOrderStatus::InDelivery->value) {
                    $this->ensureSupplierDeliveryOrder($po);
                }

                return $po;
            }, 5);

            $statusEnum = $updated->status instanceof PurchaseOrderStatus
                ? $updated->status
                : PurchaseOrderStatus::from((string) $updated->status);
            if ($statusEnum === PurchaseOrderStatus::PendingHoApproval) {
                $this->notifyHoTeamForPendingApproval($updated);
            }

            return $updated;
        }, 3);
    }

    /**
     * Hapus Purchase Order jika tidak memiliki ketergantungan transaksi.
     *
     * Aturan bisnis:
     * - Tidak boleh menghapus PO yang memiliki Supplier Delivery Order.
     * - Tidak boleh menghapus PO yang memiliki Purchase Return.
     * - Tidak boleh menghapus PO yang direferensikan oleh Supplier Bill.
     * - Tidak boleh menghapus item PO bila direferensikan oleh Supplier Bill Items.
     *
     * @param PurchaseOrder $po
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function delete(PurchaseOrder $po): void
    {
        $this->runWithRetry(function () use ($po) {
            return DB::transaction(function () use ($po) {
                $poId = (string) $po->getKey();
                $hasSdo = $po->supplierDeliveryOrders()->exists();
                if ($hasSdo) {
                    throw new \InvalidArgumentException('Tidak dapat menghapus Purchase Order karena memiliki Supplier Delivery Order terkait.');
                }

                $hasReturn = $po->purchaseReturns()->exists();
                if ($hasReturn) {
                    throw new \InvalidArgumentException('Tidak dapat menghapus Purchase Order karena memiliki Purchase Return terkait.');
                }

                $hasBill = $po->supplierBills()->exists();
                if ($hasBill) {
                    throw new \InvalidArgumentException('Tidak dapat menghapus Purchase Order karena telah direferensikan oleh Supplier Bill.');
                }

                $hasBillItems = $po->items()
                    ->whereHas('supplierBillItems')
                    ->exists();
                if ($hasBillItems) {
                    throw new \InvalidArgumentException('Tidak dapat menghapus Purchase Order karena item PO direferensikan oleh Supplier Bill Items.');
                }

                PurchaseOrderItem::query()
                    ->where('purchase_order_id', $poId)
                    ->delete();
                $po->delete();
                return null;
            }, 5);
        }, 3);
    }

    private function fillHead(PurchaseOrder $po, PurchaseOrderData $data): void
    {
        $po->supplier_id = $data->supplierId;
        $po->warehouse_id = $data->warehouseId;
        $po->order_date = $data->orderDate;
        $po->due_date = $data->dueDate;
        $po->notes = $data->notes;
        $po->delivery_cost = $data->deliveryCost ?? 0;
        $po->discount_percentage = $data->discountPercentage;
        $po->is_value_added_tax_enabled = $data->isValueAddedTaxEnabled;
        $po->value_added_tax_included = $data->valueAddedTaxIncluded;
        $po->value_added_tax_id = $data->valueAddedTaxId;
        $po->value_added_tax_percentage = $data->valueAddedTaxPercentage;
        $po->is_income_tax_enabled = $data->isIncomeTaxEnabled;
        $po->income_tax_id = $data->incomeTaxId;
        $po->income_tax_percentage = $data->incomeTaxPercentage;
        $po->supplier_invoice_number = $data->supplierInvoiceNumber;
        $po->supplier_invoice_file = $data->supplierInvoiceFile;
        $po->supplier_invoice_date = $data->supplierInvoiceDate;
    }

    /**
     * @param array<PurchaseOrderItemData> $items
     */
    private function syncItems(PurchaseOrder $po, array $items): void
    {
        PurchaseOrderItem::query()->where('purchase_order_id', $po->getKey())->delete();
        $rows = [];
        $specialPrices = [];
        $defaultPrices = [];
        if ($po->supplier_id !== null) {
            $productIds = array_unique(array_map(fn(PurchaseOrderItemData $i) => $i->productId, $items));
            if (!empty($productIds)) {
                $specialPrices = ProductSupplier::query()
                    ->where('supplier_id', $po->supplier_id)
                    ->whereIn('product_id', $productIds)
                    ->get(['product_id', 'price'])
                    ->keyBy('product_id')
                    ->map(fn($ps) => (int) $ps->price)
                    ->toArray();
                $defaultPrices = \App\Models\ProductPurchasePrice::query()
                    ->whereIn('product_id', $productIds)
                    ->get(['product_id', 'price'])
                    ->keyBy('product_id')
                    ->map(fn($pp) => (int) $pp->price)
                    ->toArray();
            }
        }
        foreach ($items as $i) {
            $price = $i->price > 0
                ? $i->price
                : (int) ($specialPrices[$i->productId] ?? ($defaultPrices[$i->productId] ?? 0));
            $subtotal = (int) ($i->quantity * $price);
            $rows[] = [
                'product_id' => $i->productId,
                'quantity' => $i->quantity,
                'price' => $price,
                'subtotal' => $subtotal,
                'notes' => $i->notes,
            ];
        }
        if (!empty($rows)) {
            $po->items()->createMany($rows);
        }
    }

    private function recalculateTotals(PurchaseOrder $po): void
    {
        $items = \App\Models\PurchaseOrderItem::query()
            ->where('purchase_order_id', $po->getKey())
            ->get(['quantity', 'price', 'subtotal']);
        $subtotal = 0;
        foreach ($items as $it) {
            $subtotal += (int) $it->subtotal;
        }
        $po->subtotal = $subtotal;
        $po->total_before_discount = $po->subtotal + (int) $po->delivery_cost;

        $discountAmount = 0;
        if ($po->discount_percentage !== null) {
            $discountAmount = (int) round(((float) $po->discount_percentage / 100.0) * (float) $po->total_before_discount);
        }
        $po->discount_amount = $discountAmount;
        $po->total_after_discount = (int) ($po->total_before_discount - $po->discount_amount);

        $vatAmount = 0;
        $totalAfterVat = $po->total_after_discount;
        if ($po->is_value_added_tax_enabled) {
            $vatPct = $po->value_added_tax_percentage !== null ? (float) $po->value_added_tax_percentage : 0.0;
            if ($po->value_added_tax_included) {
                $vatAmount = $vatPct > 0 ? (int) round(((float) $po->total_after_discount * $vatPct) / (100.0 + $vatPct)) : 0;
                $totalAfterVat = $po->total_after_discount;
            } else {
                $vatAmount = (int) round(((float) $po->total_after_discount * $vatPct) / 100.0);
                $totalAfterVat = (int) ($po->total_after_discount + $vatAmount);
            }
        }
        $po->value_added_tax_amount = $vatAmount;
        $po->total_after_value_added_tax = $totalAfterVat;

        $incomeTaxAmount = 0;
        if ($po->is_income_tax_enabled) {
            $incomePct = $po->income_tax_percentage !== null ? (float) $po->income_tax_percentage : 0.0;
            $incomeTaxAmount = (int) round(((float) $po->total_after_value_added_tax * $incomePct) / 100.0);
        }
        $po->income_tax_amount = $incomeTaxAmount;
        $po->total_after_income_tax = (int) ($po->total_after_value_added_tax - $incomeTaxAmount);
        $po->grand_total = $po->total_after_income_tax;
    }

    private function generateNumber(PurchaseOrder $po): string
    {
        $isVat = (bool) $po->is_value_added_tax_enabled;
        $monthYear = $po->order_date ? $po->order_date->format('mY') : now()->format('mY');
        $prefix = ($isVat ? 'POP' : 'PON') . '/' . $monthYear . '/';
        $last = PurchaseOrder::query()
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');
        $seq = 1;
        if (is_string($last)) {
            $parts = explode('/', $last);
            $lastSeq = (int) ($parts[2] ?? 0);
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    private function ensureSupplierDeliveryOrder(PurchaseOrder $po): void
    {
        $exists = SupplierDeliveryOrder::query()
            ->where('sourceable_type', PurchaseOrder::class)
            ->where('sourceable_id', $po->getKey())
            ->exists();
        if ($exists) {
            return;
        }

        app(\App\Services\SupplierDeliveryOrderService::class)->createFromPurchaseOrder(
            $po,
            [],
            now()->toDateString(),
            null,
            'Generated from PO ' . (string) ($po->number ?? ''),
        );
    }

    private function generateSupplierDeliveryOrderNumber(): string
    {
        $prefix = 'SJ/' . now()->format('Ym') . '/';
        $last = SupplierDeliveryOrder::query()
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');
        $seq = 1;
        if (is_string($last)) {
            $parts = explode('/', $last);
            $lastSeq = (int) ($parts[2] ?? 0);
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
