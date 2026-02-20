<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\{StockBucket, StockDocumentType, StockDocumentReason, GoodsComeSourceType};
use App\Models\{StockDocumentItem, StockDocument, GoodsCome, StockTransfer, StockAdjustment, SalesReturn, SupplierDeliveryOrder};
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $stock = $this->resource->stock;
        $product = $stock?->product;
        $warehouse = $stock?->warehouse;
        $user = $this->resource->user;
        $refLabel = null;
        $refId = $this->resource->referencable_id ? (string) $this->resource->referencable_id : null;
        $refType = $this->resource->referencable_type ? (string) $this->resource->referencable_type : null;
        if ($refId && $refType) {
            switch ($refType) {
                case StockDocumentItem::class:
                    $docId = StockDocumentItem::query()->where('id', $refId)->value('stock_document_id');
                    if (is_string($docId)) {
                        $doc = StockDocument::query()->where('id', $docId)->first(['number', 'type', 'reason']);
                        if ($doc) {
                            $typeVal = $doc->type instanceof StockDocumentType ? (string) $doc->type->value : (string) $doc->type;
                            $reasonVal = $doc->reason instanceof StockDocumentReason ? (string) $doc->reason->value : (string) $doc->reason;
                            $typeLabel = StockDocumentType::from($typeVal)->label();
                            $reasonLabel = StockDocumentReason::from($reasonVal)->label();
                            $refLabel = 'Surat Stok ' . $typeLabel . ' ' . (string) $doc->number . ' (' . $reasonLabel . ')';
                        } else {
                            $refLabel = 'Surat Stok';
                        }
                    }
                    break;
                case GoodsCome::class:
                    $gc = GoodsCome::query()->where('id', $refId)->first(['referencable_id', 'referencable_type', 'source_type']);
                    if ($gc) {
                        $srcVal = $gc->source_type instanceof GoodsComeSourceType ? (string) $gc->source_type->value : (string) $gc->source_type;
                        $srcLabel = GoodsComeSourceType::from($srcVal)->label();
                        if ($gc->referencable_type === SupplierDeliveryOrder::class && $gc->referencable_id) {
                            $sdoNumber = \App\Models\SupplierDeliveryOrder::query()->where('id', (string) $gc->referencable_id)->value('number');
                            $refLabel = $sdoNumber ? ('Penerimaan SDO ' . (string) $sdoNumber) : ('Penerimaan Barang (' . $srcLabel . ')');
                        } else {
                            $refLabel = 'Penerimaan Barang (' . $srcLabel . ')';
                        }
                    }
                    break;
                case StockTransfer::class:
                    $number = StockTransfer::query()->where('id', $refId)->value('number');
                    $refLabel = $number ? ('Mutasi Stok ' . (string) $number) : 'Mutasi Stok';
                    break;
                case StockAdjustment::class:
                    $number = StockAdjustment::query()->where('id', $refId)->value('number');
                    $refLabel = $number ? ('Penyesuaian Stok ' . (string) $number) : 'Penyesuaian Stok';
                    break;
                case SalesReturn::class:
                    $number = SalesReturn::query()->where('id', $refId)->value('number');
                    $refLabel = $number ? ('Retur Penjualan ' . (string) $number) : 'Retur Penjualan';
                    break;
                default:
                    $refLabel = null;
                    break;
            }
        }

        return [
            'id' => (string) $this->resource->getKey(),
            'date' => $this->resource->created_at ? $this->resource->created_at->format('Y-m-d') : null,
            'time' => $this->resource->created_at ? $this->resource->created_at->format('H:i') : null,
            'type' => (string) $this->resource->type->label(),
            'quantity' => (int) $this->resource->quantity,
            'unit_price' => (int) ($this->resource->unit_price ?? 0),
            'subtotal' => (int) ($this->resource->subtotal ?? 0),
            'balance_before' => (int) ($this->resource->balance_before ?? 0),
            'balance_after' => (int) $this->resource->balance_after,
            'last_hpp' => (int) ($this->resource->last_hpp ?? 0),
            'referencable' => $refLabel,
            'notes' => $this->resource->notes ? (string) $this->resource->notes : null,
            'bucket' => $stock && $stock->bucket ? (string) $stock->bucket->value : null,
            'bucket_label' => ($stock && $stock->bucket === StockBucket::Vat) ? 'PPN' : 'Non PPN',
            'warehouse' => $warehouse ? [
                'id' => (string) $warehouse->getKey(),
                'name' => (string) $warehouse->name,
            ] : [
                'id' => null,
                'name' => null,
            ],
            'product' => $product ? [
                'id' => (string) $product->getKey(),
                'name' => (string) $product->name,
            ] : [
                'id' => null,
                'name' => null,
            ],
            'user' => $user ? [
                'id' => (string) $user->getKey(),
                'name' => (string) $user->name,
            ] : [
                'id' => null,
                'name' => null,
            ],
        ];
    }
}
