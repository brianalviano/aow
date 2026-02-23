<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\Models\DropPoint;

class HomeService
{
    /**
     * Get data for the customer home page.
     *
     * @return array
     */
    public function getHomeData(): array
    {
        $activeDropPoints = DropPoint::query()
            ->where('is_active', true)
            ->get();

        return [
            'totalDropPoints' => $activeDropPoints->count(),
            'dropPoints'      => $activeDropPoints,
        ];
    }
}
