<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\{Inertia, Response};
use App\Models\{CashierSession, User};
use App\Enums\CashSessionStatus;

final class CashHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $status = (string) $request->string('status')->toString();
        $openedFrom = (string) $request->string('opened_from')->toString();
        $openedTo = (string) $request->string('opened_to')->toString();
        $userId = (string) $request->string('user_id')->toString();

        $query = CashierSession::query()
            ->with(['user'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('note', 'ilike', "%{$q}%")
                        ->orWhereHas('user', function ($su) use ($q) {
                            $su->where('name', 'ilike', "%{$q}%");
                        });
                });
            })
            ->when($status !== '', function ($builder) use ($status) {
                $builder->where('status', $status);
            })
            ->when($userId !== '', function ($builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->when($openedFrom !== '', function ($builder) use ($openedFrom) {
                $builder->whereDate('opened_at', '>=', $openedFrom);
            })
            ->when($openedTo !== '', function ($builder) use ($openedTo) {
                $builder->whereDate('opened_at', '<=', $openedTo);
            })
            ->orderByDesc('opened_at')
            ->orderByDesc('created_at');

        $perPage = (int) $request->integer('per_page', 10);
        $sessions = $query->paginate($perPage)->appends([
            'q' => $q,
            'status' => $status,
            'opened_from' => $openedFrom,
            'opened_to' => $openedTo,
            'user_id' => $userId,
        ]);

        $items = collect($sessions->items())->map(function ($s) {
            $statusValue = $s->status instanceof CashSessionStatus
                ? $s->status->value
                : (string) $s->status;
            return [
                'id' => (string) $s->id,
                'opened_at' => $s->opened_at ? (string) $s->opened_at->toDateTimeString() : null,
                'closed_at' => $s->closed_at ? (string) $s->closed_at->toDateTimeString() : null,
                'starting_cash' => (int) $s->starting_cash,
                'expected_cash' => (int) $s->expected_cash,
                'actual_cash' => (int) $s->actual_cash,
                'variance' => (int) $s->variance,
                'status' => $statusValue,
                'status_label' => CashSessionStatus::tryFrom($statusValue)?->label() ?? 'Tidak Diketahui',
                'user' => $s->user ? [
                    'id' => (string) $s->user->id,
                    'name' => (string) $s->user->name,
                ] : ['id' => null, 'name' => null],
            ];
        })->all();

        $userOptions = User::query()
            ->orderBy('name')
            ->select(['id', 'name'])
            ->get()
            ->map(fn($u) => ['value' => (string) $u->id, 'label' => (string) $u->name])
            ->all();

        return Inertia::render('Domains/Admin/Sales/Cash/Index', [
            'sessions' => $items,
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
                'last_page' => $sessions->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'status' => $status,
                'opened_from' => $openedFrom,
                'opened_to' => $openedTo,
                'user_id' => $userId,
            ],
            'statusOptions' => array_map(fn($s) => ['value' => $s->value, 'label' => $s->label()], CashSessionStatus::cases()),
            'userOptions' => $userOptions,
        ]);
    }
}
