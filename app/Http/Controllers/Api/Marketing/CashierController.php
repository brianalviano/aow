<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\HandlesApiExceptions;
use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Requests\Sales\{OpenCashierSessionRequest, CloseCashierSessionRequest};
use App\Models\{CashierSession, PaymentAllocation, Sales};
use App\Services\CashierSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CashierController extends Controller
{
    use HandlesApiExceptions;
    use RespondsWithJson;

    public function open(OpenCashierSessionRequest $request, CashierSessionService $service): JsonResponse
    {
        return $this->apiTry(function () use ($request, $service) {
            $shift = $service->openShift(
                (string) $request->user()->getAuthIdentifier(),
                (int) $request->input('opening_balance'),
            );
            return $this->apiResponse('Kasir dibuka', [
                'shift' => $this->buildShiftPayload($request, $shift),
            ]);
        }, $request, [
            'user_id' => (string) $request->user()->getAuthIdentifier(),
            'opening_balance' => (int) $request->input('opening_balance'),
        ]);
    }

    public function close(CloseCashierSessionRequest $request, CashierSessionService $service): JsonResponse
    {
        return $this->apiTry(function () use ($request, $service) {
            $shift = $service->closeShift(
                (string) $request->user()->getAuthIdentifier(),
                (string) $request->input('session_id'),
                (int) $request->input('actual_closing_balance'),
            );
            return $this->apiResponse('Kasir ditutup', [
                'shift' => $this->buildShiftPayload($request, $shift),
            ]);
        }, $request, [
            'user_id' => (string) $request->user()->getAuthIdentifier(),
            'session_id' => (string) $request->input('session_id'),
            'actual_closing_balance' => (int) $request->input('actual_closing_balance'),
        ]);
    }

    private function buildShiftPayload(Request $request, CashierSession $shift): array
    {
        $totalSales = (int) Sales::query()
            ->where('cashier_session_id', (string) $shift->id)
            ->toBase()
            ->sum('grand_total');
        $totalCashIn = (int) PaymentAllocation::query()
            ->join('sales', 'payment_allocations.referencable_id', '=', 'sales.id')
            ->where('payment_allocations.referencable_type', Sales::class)
            ->where('sales.cashier_session_id', (string) $shift->id)
            ->whereNull('payment_allocations.voided_at')
            ->toBase()
            ->sum('payment_allocations.amount');

        $payload = [
            'id' => (string) $shift->id,
            'opened_at' => (string) ($shift->opened_at ? $shift->opened_at->toDateTimeString() : ''),
            'closed_at' => null,
            'opening_balance' => (int) $shift->starting_cash,
            'expected_closing_balance' => (int) ((int) $shift->starting_cash + (int) $totalCashIn),
            'actual_closing_balance' => null,
            'total_sales' => (int) $totalSales,
            'total_cash_in' => (int) $totalCashIn,
            'variance' => null,
        ];

        if ($shift->closed_at !== null) {
            $payload['closed_at'] = (string) $shift->closed_at->toDateTimeString();
            $payload['actual_closing_balance'] = (int) $shift->actual_cash;
            $payload['variance'] = (int) $shift->variance;
        }

        return $payload;
    }
}
