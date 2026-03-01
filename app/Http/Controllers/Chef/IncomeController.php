<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use App\Models\ChefTransfer;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing chef income and transfers.
 */
class IncomeController extends Controller
{
    /**
     * Display a listing of income transfers for the chef.
     *
     * @return \Inertia\Response
     */
    public function index(): Response
    {
        $chef = Auth::guard('chef')->user();

        $transfers = ChefTransfer::query()
            ->where('chef_id', $chef->id)
            ->latest('transferred_at')
            ->get();

        return Inertia::render('Domains/Chef/Income/Index', [
            'transfers' => $transfers,
        ]);
    }
}
