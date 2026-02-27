<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Chef;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for recording a new transfer to a Chef.
 */
class StoreChefTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gross_amount'   => ['required', 'integer', 'min:1'],
            'note'           => ['nullable', 'string'],
            'transfer_proof' => ['nullable', 'image', 'max:2048'],
            'transferred_at' => ['required', 'date'],
        ];
    }
}
