<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\Setting\OrderSettingsDTO;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TermsAndConditionController extends Controller
{
    /**
     * Display the terms of service page.
     *
     * @return Response
     */
    public function index(): Response
    {
        $orderSettings = OrderSettingsDTO::load();
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->pluck('name')->toArray();

        // format array into "A, B, C, dan D"
        $paymentMethodsString = '';
        if (count($paymentMethods) > 1) {
            $lastMethod = array_pop($paymentMethods);
            $paymentMethodsString = implode(', ', $paymentMethods) . ', dan ' . $lastMethod;
        } elseif (count($paymentMethods) === 1) {
            $paymentMethodsString = $paymentMethods[0];
        }

        $dropPointCategories = array_map(
            fn(\App\Enums\DropPointCategory $category) => strtolower($category->label()),
            array_filter(
                \App\Enums\DropPointCategory::cases(),
                fn(\App\Enums\DropPointCategory $category) => $category !== \App\Enums\DropPointCategory::OTHER
            )
        );
        $dropPointCategoriesString = implode(', ', $dropPointCategories) . ', dll';

        return Inertia::render('Domains/Customer/TermsAndCondition/Index', [
            'appUrl' => config('app.url'),
            'orderMinDaysAhead' => $orderSettings->orderMinDaysAhead,
            'paymentMethodsStr' => $paymentMethodsString,
            'dropPointCategoriesStr' => $dropPointCategoriesString,
        ]);
    }
}
