<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\DropPointResource;
use App\Models\DropPoint;
use Inertia\Inertia;
use Inertia\Response;

class DropPointController extends Controller
{
    /**
     * Display the specified drop point detail page.
     */
    public function show(string $id): Response
    {
        $dropPoint = DropPoint::query()
            ->where('is_active', true)
            ->findOrFail($id);

        return Inertia::render('Domains/Customer/DropPoint/Show', [
            'dropPoint' => new DropPointResource($dropPoint),
        ]);
    }
}
