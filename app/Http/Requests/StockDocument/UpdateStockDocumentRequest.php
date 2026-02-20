<?php

declare(strict_types=1);

namespace App\Http\Requests\StockDocument;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\StockDocument;

/**
 * Validasi pembaruan metadata Dokumen Stok manual.
 *
 * Tidak mengizinkan perubahan type/reason/bucket/items karena mempengaruhi saldo stok.
 *
 * @author PJD
 */
class UpdateStockDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('logistic-stockcard');
    }

    public function rules(): array
    {
        $routeParam = $this->route('stock_document');
        $docId = $routeParam instanceof StockDocument ? (string) $routeParam->getKey() : (string) $routeParam;

        return [
            'document_date' => ['required', 'date'],
            'number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stock_documents', 'number')->ignore($docId, 'id'),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}

