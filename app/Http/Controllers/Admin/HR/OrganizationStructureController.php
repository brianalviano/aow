<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationStructureController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with(['role'])
            ->whereHas('role', function ($r) {
                $r->whereNot('name', 'Super Admin');
            })
            ->get(['id', 'name', 'email', 'role_id']);

        $groups = $users->groupBy(function (User $u) {
            $roleName = $u->role?->name;
            return $roleName ?: 'Unknown';
        });

        $roleNames = $groups->keys()->all();
        $computePriority = function (string $name): int {
            $n = trim($name);
            if ($n === 'Director') {
                return 0;
            }
            if (str_starts_with($n, 'Manager ')) {
                return 1;
            }
            if ($n === 'Cashier' || $n === 'Marketing') {
                return 2;
            }
            if (str_starts_with($n, 'Staff ')) {
                return 3;
            }
            if ($n === 'Unknown') {
                return 99;
            }
            return 99;
        };
        $sortedRoleNames = collect($roleNames)->sort(function ($a, $b) use ($computePriority) {
            $pa = $computePriority(is_string($a) ? $a : (string) $a);
            $pb = $computePriority(is_string($b) ? $b : (string) $b);
            if ($pa === $pb) {
                return (is_string($a) ? $a : (string) $a) <=> (is_string($b) ? $b : (string) $b);
            }
            return $pa <=> $pb;
        });

        $orderedRoles = $sortedRoleNames->values()->all();
        if (empty($orderedRoles)) {
            return Inertia::render('Domains/Admin/HR/OrganizationStructure/Index', [
                'structure' => [],
            ]);
        }

        $levels = [];
        foreach ($orderedRoles as $roleName) {
            $levels[] = ($groups->get($roleName) ?? collect())
                ->sortBy('name')
                ->values()
                ->all();
        }

        $rootUsers = $levels[0] ?? [];
        $rootRoleName = $orderedRoles[0] ?? null;

        if (empty($rootUsers)) {
            $rootNode = [
                'id' => 'placeholder-' . strtolower((string) ($rootRoleName ?? 'unknown')),
                'name' => 'Belum ada user',
                'role' => $rootRoleName ? (string) $rootRoleName : null,
                'email' => null,
                'kind' => 'position',
                'children' => [],
            ];
        } else {
            $rootUser = $rootUsers[0];
            $rootNode = [
                'id' => (string) $rootUser->id,
                'name' => (string) $rootUser->name,
                'role' => (string) $rootUser->role?->name,
                'email' => (string) $rootUser->email,
                'kind' => 'employee',
                'children' => [],
            ];
        }

        $parents = [&$rootNode];
        for ($i = 1; $i < count($levels); $i++) {
            $currentUsers = $levels[$i];
            $currentRoleName = $orderedRoles[$i] ?? null;
            $newParents = [];
            $pcount = count($parents);
            if ($pcount === 0) {
                break;
            }

            if (empty($currentUsers)) {
                for ($p = 0; $p < $pcount; $p++) {
                    $node = [
                        'id' => 'placeholder-' . strtolower((string) ($currentRoleName ?? 'unknown')) . '-' . $p,
                        'name' => 'Belum ada user',
                        'role' => $currentRoleName ? (string) $currentRoleName : null,
                        'email' => null,
                        'kind' => 'position',
                        'children' => [],
                    ];
                    $parents[$p]['children'][] = $node;
                    $newParents[] = &$parents[$p]['children'][count($parents[$p]['children']) - 1];
                }
                $parents = $newParents;
                continue;
            }

            $j = 0;
            foreach ($currentUsers as $u) {
                $parentIndex = $j % $pcount;
                $node = [
                    'id' => (string) $u->id,
                    'name' => (string) $u->name,
                    'role' => (string) $u->role?->name,
                    'email' => (string) $u->email,
                    'kind' => 'employee',
                    'children' => [],
                ];
                $parents[$parentIndex]['children'][] = $node;
                $newParents[] = &$parents[$parentIndex]['children'][count($parents[$parentIndex]['children']) - 1];
                $j++;
            }
            $parents = $newParents;
        }

        return Inertia::render('Domains/Admin/HR/OrganizationStructure/Index', [
            'structure' => [$rootNode],
        ]);
    }
}
