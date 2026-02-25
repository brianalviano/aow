<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\User\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\{StoreUserRequest, UpdateUserRequest};
use App\Http\Resources\{RoleResource, UserResource};
use App\Models\{Role, User};
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Handles admin CRUD operations for users.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $roleId = $request->query('role_id');
        $limit = (int) $request->query('limit', 15);

        $users = $this->userService->getPaginated($limit, $search, $roleId);
        $roles = Role::orderBy('name', 'asc')->get();

        return Inertia::render('Domains/Admin/User/Index', [
            'users' => UserResource::collection($users),
            'roles' => RoleResource::collection($roles),
            'filters' => [
                'search' => $search,
                'role_id' => $roleId,
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        $roles = Role::orderBy('name', 'asc')->get();

        return Inertia::render('Domains/Admin/User/Form', [
            'roles' => RoleResource::collection($roles),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $data = UserData::fromStoreRequest($request);

            $this->userService->createUser($data);

            Inertia::flash('toast', [
                'message' => 'User berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.users.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat User: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $user->load('role');
        $roles = Role::orderBy('name', 'asc')->get();

        return Inertia::render('Domains/Admin/User/Form', [
            'user' => new UserResource($user),
            'roles' => RoleResource::collection($roles),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $data = UserData::fromUpdateRequest($request);

            $this->userService->updateUser($user, $data);

            Inertia::flash('toast', [
                'message' => 'User berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.users.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui User: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            // Prevent deleting self
            if ($user->id === auth()->id()) {
                Inertia::flash('toast', [
                    'message' => 'Anda tidak dapat menghapus akun Anda sendiri',
                    'type' => 'error',
                ]);
                return back();
            }

            $this->userService->deleteUser($user);

            Inertia::flash('toast', [
                'message' => 'User berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.users.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus User: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
