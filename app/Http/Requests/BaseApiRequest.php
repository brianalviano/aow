<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * BaseApiRequest
 *
 * Mengubah perilaku default FormRequest agar seluruh kegagalan validasi
 * dan otorisasi mengembalikan JSON envelope standar dengan HTTP 200:
 * - success: false
 * - message: alasan kegagalan ("Validasi gagal" / "Tidak diizinkan")
 * - data: detail errors (untuk validasi) atau objek kosong.
 *
 * @author
 * @package Http\Requests
 */
abstract class BaseApiRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'data' => $validator->errors()->toArray(),
        ], 200));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Tidak diizinkan',
            'data' => [],
        ], 200));
    }
}
