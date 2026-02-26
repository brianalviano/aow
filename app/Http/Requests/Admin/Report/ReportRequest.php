<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates report filter parameters for the admin reports feature.
 *
 * @property-read string|null $date_from  Start date filter (Y-m-d).
 * @property-read string|null $date_to    End date filter (Y-m-d), must be >= date_from.
 * @property-read string|null $drop_point_id UUID of the drop point to filter by.
 * @property-read string      $type       Report type: 'orders' or 'products'.
 */
class ReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for report filters.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date', 'after_or_equal:date_from'],
            'drop_point_id' => ['nullable', 'exists:drop_points,id'],
            'type'          => ['nullable', 'in:orders,products'],
        ];
    }
}
