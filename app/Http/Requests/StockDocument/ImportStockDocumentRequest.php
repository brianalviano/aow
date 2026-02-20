<?php

declare(strict_types=1);

namespace App\Http\Requests\StockDocument;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\{StockDocumentType, StockDocumentReason, StockBucket};

/**
 * Validasi untuk impor Dokumen Stok dari file Excel.
 *
 * Menerima parameter konteks dari UI:
 * - type (in/out)
 * - reason sesuai type
 * - bucket (vat/non_vat)
 * - notes (opsional)
 * - warehouse_id (wajib, tujuan dokumen)
 *
 * File Excel hanya mengandung 4 kolom: Product Name / SKU, quantity, unit_cost, notes.
 */
class ImportStockDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'type' => ['required', Rule::in(StockDocumentType::values())],
            'reason' => ['required', 'string', Rule::in(StockDocumentReason::values())],
            'bucket' => ['nullable', 'string', Rule::in(StockBucket::values())],
            'notes' => ['nullable', 'string', 'max:1000'],
            'warehouse_id' => ['required', 'string', 'exists:warehouses,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = (string) $this->input('type', '');
            $reason = (string) $this->input('reason', '');
            if ($type !== '' && $reason !== '') {
                $allowed = array_map(static fn(StockDocumentReason $r) => $r->value, StockDocumentReason::forType(StockDocumentType::from($type)));
                if (!in_array($reason, $allowed, true)) {
                    $v->errors()->add('reason', 'Reason tidak valid untuk type yang dipilih.');
                }
            }
        });
    }
}
