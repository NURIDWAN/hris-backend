<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $tenant = app('tenant');

        $roles = Role::where('company_id', $tenant->id)
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('settings.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $permissions = Permission::orderBy('name')->get();

        return view('settings.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($tenant) {
                    $exists = Role::where('name', $value)
                        ->where('company_id', $tenant->id)
                        ->exists();

                    if ($exists) {
                        $fail('Role dengan nama ini sudah ada.');
                    }
                },
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // Set team context
        setPermissionsTeamId($tenant->id);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->givePermissionTo($validated['permissions']);
        }

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): View
    {
        $tenant = app('tenant');

        // Ensure role belongs to current company
        if ($role->company_id !== $tenant->id) {
            abort(404);
        }

        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('settings.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $tenant = app('tenant');

        // Ensure role belongs to current company
        if ($role->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($tenant, $role) {
                    $exists = Role::where('name', $value)
                        ->where('company_id', $tenant->id)
                        ->where('id', '!=', $role->id)
                        ->exists();

                    if ($exists) {
                        $fail('Role dengan nama ini sudah ada.');
                    }
                },
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $tenant = app('tenant');

        // Ensure role belongs to current company
        if ($role->company_id !== $tenant->id) {
            abort(404);
        }

        // Check if role has users assigned
        if ($role->users()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Role tidak dapat dihapus karena masih digunakan oleh pengguna.');
        }

        $role->delete();

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}
