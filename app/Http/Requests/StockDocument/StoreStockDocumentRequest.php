<?php

declare(strict_types=1);

namespace App\Http\Requests\StockDocument;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\{StockDocumentType, StockDocumentReason, StockBucket, StockDocumentStatus};
use App\Models\{PurchaseOrder, PurchaseReturn, Sales, SalesReturn, StockOpname};

/**
 * Validasi pembuatan Dokumen Stok manual.
 *
 * @author PJD
 */
class StoreStockDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('logistic-stockcard');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'type' => ['required', Rule::in(StockDocumentType::values())],
            'reason' => ['required', Rule::in(StockDocumentReason::values())],
            'document_date' => ['required', 'date'],
            'number' => ['nullable', 'string', 'max:255', 'unique:stock_documents,number'],
            'bucket' => ['nullable', Rule::in(StockBucket::values())],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(StockDocumentStatus::values())],
            'sourceable_type' => ['nullable', Rule::in([
                PurchaseOrder::class,
                PurchaseReturn::class,
                Sales::class,
                SalesReturn::class,
                StockOpname::class,
            ])],
            'sourceable_id' => ['nullable', 'uuid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.product_division_id' => ['nullable', 'uuid', 'exists:product_divisions,id'],
            'items.*.product_rack_id' => ['nullable', 'uuid', 'exists:product_racks,id'],
            'items.*.owner_user_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = (string) ($this->input('sourceable_type') ?? '');
            $id = (string) ($this->input('sourceable_id') ?? '');
            if ($type !== '' && $id !== '') {
                if ($type === PurchaseOrder::class) {
                    $exists = PurchaseOrder::query()->whereKey($id)->exists();
                } elseif ($type === PurchaseReturn::class) {
                    $exists = PurchaseReturn::query()->whereKey($id)->exists();
                } elseif ($type === Sales::class) {
                    $exists = Sales::query()->whereKey($id)->exists();
                } elseif ($type === SalesReturn::class) {
                    $exists = SalesReturn::query()->whereKey($id)->exists();
                } elseif ($type === StockOpname::class) {
                    $exists = StockOpname::query()->whereKey($id)->exists();
                } else {
                    $exists = false;
                }
                if (!$exists) {
                    $v->errors()->add('sourceable_id', 'Referensi sumber tidak ditemukan untuk tipe yang dipilih.');
                }
            }
            $docTypeStr = (string) ($this->input('type') ?? '');
            $reasonStr = (string) ($this->input('reason') ?? '');
            if ($docTypeStr !== '' && $reasonStr !== '') {
                $docType = StockDocumentType::tryFrom($docTypeStr);
                $reason = StockDocumentReason::tryFrom($reasonStr);
                if ($docType !== null && $reason !== null) {
                    $allowed = array_map(fn($r) => $r->value, StockDocumentReason::forType($docType));
                    if (!in_array($reason->value, $allowed, true)) {
                        $v->errors()->add('reason', 'Alasan tidak sesuai dengan jenis dokumen stok.');
                    }
                }
            }
        });
    }
}
