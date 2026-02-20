<?php

namespace App\Http\Controllers\Admin\HR;

use App\DTOs\Employee\EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Role;
use App\Models\User;
use App\Services\EmployeeService;
use App\Http\Requests\Employee\ImportEmployeeRequest;
use App\Exports\EmployeesExport;
use App\Exports\EmployeesImportTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $roleId = (string) $request->string('role_id')->toString();
        $joinDateFrom = (string) $request->string('join_date_from')->toString();
        $joinDateTo = (string) $request->string('join_date_to')->toString();

        $query = User::query()
            ->with(['role'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%")
                        ->orWhere('username', 'ilike', "%{$q}%")
                        ->orWhere('phone_number', 'ilike', "%{$q}%");
                });
            })
            ->when($roleId !== '', function ($builder) use ($roleId) {
                $builder->where('role_id', $roleId);
            })
            ->when($joinDateFrom !== '', function ($builder) use ($joinDateFrom) {
                $builder->whereDate('join_date', '>=', $joinDateFrom);
            })
            ->when($joinDateTo !== '', function ($builder) use ($joinDateTo) {
                $builder->whereDate('join_date', '<=', $joinDateTo);
            })
            ->whereHas('role', function ($r) {
                $r->whereNot('name', 'Super Admin');
            })
            ->orderBy('name');

        $perPage = (int) $request->integer('per_page', 10);
        $employees = $query->paginate($perPage)->appends([
            'q' => $q,
            'role_id' => $roleId,
            'join_date_from' => $joinDateFrom,
            'join_date_to' => $joinDateTo,
        ]);
        $items = array_map(
            fn($u) => EmployeeResource::make($u)->toArray($request),
            $employees->items(),
        );

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Domains/Admin/HR/Employees/Index', [
            'employees' => $items,
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'role_id' => $roleId,
                'join_date_from' => $joinDateFrom,
                'join_date_to' => $joinDateTo,
            ],
            'roles' => $roles->map(fn($r) => ['id' => (string) $r->id, 'name' => (string) $r->name]),
        ]);
    }

    public function create(): Response
    {
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Domains/Admin/HR/Employees/Form', [
            'roles' => $roles->map(fn($r) => ['id' => (string) $r->id, 'name' => (string) $r->name]),
            'employee' => null,
        ]);
    }

    public function store(StoreEmployeeRequest $request, EmployeeService $service): RedirectResponse
    {
        try {
            $service->create(EmployeeData::fromStoreRequest($request));
            Inertia::flash('toast', [
                'message' => 'Karyawan berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('employees.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat karyawan: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function show(User $employee): Response
    {
        $employee->load(['role']);

        return Inertia::render('Domains/Admin/HR/Employees/Show', [
            'employee' => EmployeeResource::make($employee)->toArray(request()),
        ]);
    }

    public function edit(User $employee): Response
    {
        $employee->load(['role']);
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Domains/Admin/HR/Employees/Form', [
            'roles' => $roles->map(fn($r) => ['id' => (string) $r->id, 'name' => (string) $r->name]),
            'employee' => EmployeeResource::make($employee)->toArray(request()),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, User $employee, EmployeeService $service): RedirectResponse
    {
        try {
            $service->update($employee, EmployeeData::fromUpdateRequest($request));
            Inertia::flash('toast', [
                'message' => 'Karyawan berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('employees.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui karyawan: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(User $employee, EmployeeService $service): RedirectResponse
    {
        try {
            $service->delete($employee);
            Inertia::flash('toast', [
                'message' => 'Karyawan berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('employees.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus karyawan: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('employees.index');
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        $q = (string) $request->string('q')->toString();
        return Excel::download(new EmployeesExport($q), 'employees.xlsx');
    }

    public function import(ImportEmployeeRequest $request, EmployeeService $service): RedirectResponse
    {
        $file = $request->file('file');
        Excel::import(new \App\Imports\EmployeesImport($service), $file);
        return redirect()->route('employees.index');
    }

    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new EmployeesImportTemplateExport(), 'employees_import_template.xlsx');
    }
}
