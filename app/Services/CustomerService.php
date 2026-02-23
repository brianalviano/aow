<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    /**
     * Get paginated customers with optional search.
     *
     * @param int $perPage
     * @param string|null $search
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return Customer::query()
            ->with('dropPoint')
            ->when($search, function ($query, $term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('dropPoint', function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%");
                    });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
