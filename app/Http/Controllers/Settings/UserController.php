<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $query = User::where('company_id', $tenant->id)
            ->with('roles');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->orderBy('name')->paginate(15);

        // Get company-specific roles
        $roles = Role::where('company_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('settings.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $tenant = app('tenant');

        // Get company-specific roles
        $roles = Role::where('company_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('settings.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $tenant = app('tenant');
        $validated = $request->validated();

        // Set team context for role assignment
        setPermissionsTeamId($tenant->id);

        $user = User::create([
            'company_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (isset($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return redirect()->route('settings.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        $tenant = app('tenant');

        if ($user->company_id !== $tenant->id) {
            abort(404);
        }

        // Get company-specific roles
        $roles = Role::where('company_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('settings.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $tenant = app('tenant');

        if ($user->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validated();

        // Set team context for role assignment
        setPermissionsTeamId($tenant->id);

        $updateData = [
            'name' => $validated['name'],
            'email' => $user->isDemoAccount() ? $user->email : $validated['email'], // Don't change demo email
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        // Only update password if provided and not demo account
        if (! empty($validated['password']) && ! $user->isDemoAccount()) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Update roles
        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('settings.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $tenant = app('tenant');

        if ($user->company_id !== $tenant->id) {
            abort(404);
        }

        // Cannot delete self
        if ($user->id === auth()->id()) {
            return redirect()->route('settings.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('settings.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $tenant = app('tenant');

        if ($user->company_id !== $tenant->id) {
            abort(404);
        }

        // Cannot toggle own status
        if ($user->id === auth()->id()) {
            return redirect()->route('settings.users.index')
                ->with('error', 'Anda tidak dapat mengubah status akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('settings.users.index')
            ->with('success', "Pengguna berhasil {$status}.");
    }
}
