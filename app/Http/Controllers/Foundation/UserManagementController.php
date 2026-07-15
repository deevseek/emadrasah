<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\UserRequest;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = (string) $request->string('q');
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query): mixed => $query->where('is_active', $request->boolean('status')))
            ->when($request->filled('role'), fn ($query): mixed => $query->role((string) $request->string('role')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('foundation.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('display_name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('foundation.users.form', [
            'user' => new User,
            'roles' => Role::query()->orderBy('display_name')->get(),
        ]);
    }

    public function store(UserRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $logger): User {
            $user = User::create($request->safe()->except('roles'));
            $user->syncRoles($request->input('roles', []));
            $logger->log('user.created', $user, [], $user->toArray());

            return $user;
        });

        return redirect()->route('users.show', $user)->with('status', 'Pengguna berhasil dibuat.');
    }

    public function show(User $user): View
    {
        return view('foundation.users.show', [
            'user' => $user->load('roles'),
            'histories' => LoginHistory::where('user_id', $user->id)->latest('attempted_at')->paginate(10),
        ]);
    }

    public function edit(User $user): View
    {
        return view('foundation.users.form', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->orderBy('display_name')->get(),
        ]);
    }

    public function update(UserRequest $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        DB::transaction(function () use ($request, $user, $logger): void {
            $old = $user->toArray();
            $data = $request->safe()->except('roles');

            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $this->guardLastSuperAdminRole($user, $request->input('roles', []));
            $user->update($data);
            $user->syncRoles($request->input('roles', []));
            $logger->log('user.updated', $user, $old, $user->fresh()->toArray());
        });

        return redirect()->route('users.show', $user)->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function toggle(User $user, ActivityLogger $logger): RedirectResponse
    {
        DB::transaction(function () use ($user, $logger): void {
            if ($user->is_active) {
                $this->guardLastActiveSuperAdmin($user);
            }

            $old = $user->toArray();
            $user->update(['is_active' => ! $user->is_active]);
            $logger->log($user->is_active ? 'user.activated' : 'user.deactivated', $user, $old, $user->fresh()->toArray());
        });

        return back()->with('status', 'Status pengguna berhasil diperbarui.');
    }

    private function guardLastSuperAdminRole(User $user, array $newRoles): void
    {
        if (! $user->hasRole('super-admin')) {
            return;
        }

        $keepsSuperAdmin = collect($newRoles)
            ->map(fn (mixed $role): string => (string) $role)
            ->contains('super-admin');

        if ($keepsSuperAdmin) {
            return;
        }

        $this->guardLastActiveSuperAdmin($user);
    }

    private function guardLastActiveSuperAdmin(User $user): void
    {
        $hasOtherSuperAdmin = User::query()
            ->whereKeyNot($user->getKey())
            ->where('is_active', true)
            ->role('super-admin')
            ->exists();

        if (! $hasOtherSuperAdmin) {
            throw ValidationException::withMessages([
                'roles' => 'Super Admin terakhir tidak boleh dinonaktifkan atau kehilangan role Super Admin.',
            ]);
        }
    }
}
