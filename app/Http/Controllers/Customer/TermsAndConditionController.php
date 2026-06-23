<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\Setting\OrderSettingsDTO;
use App\Enums\DropPointCategory;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Inertia\Inertia;
use Inertia\Response;

class TermsAndConditionController extends Controller
{
    /**
     * Display the terms of service page.
     */
    public function index(): Response
    {
        $orderSettings = OrderSettingsDTO::load();
        $paymentMethods = PaymentMethod::where('is_active', true)->pluck('name')->toArray();

        // format array into "A, B, C, dan D"
        $paymentMethodsString = '';
        if (count($paymentMethods) > 1) {
            $lastMethod = array_pop($paymentMethods);
            $paymentMethodsString = implode(', ', $paymentMethods).', dan '.$lastMethod;
        } elseif (count($paymentMethods) === 1) {
            $paymentMethodsString = $paymentMethods[0];
        }

        $dropPointCategories = array_map(
            fn (DropPointCategory $category) => strtolower($category->label()),
            array_filter(
                DropPointCategory::cases(),
                fn (DropPointCategory $category) => $category !== DropPointCategory::OTHER
            )
        );
        $dropPointCategoriesString = implode(', ', $dropPointCategories).', dll';

        return Inertia::render('Domains/Customer/TermsAndCondition/Index', [
            'appUrl' => config('app.url'),
            'orderMinDaysAhead' => $orderSettings->orderMinDaysAhead,
            'paymentMethodsStr' => $paymentMethodsString,
            'dropPointCategoriesStr' => $dropPointCategoriesString,
        ]);
    }
}
