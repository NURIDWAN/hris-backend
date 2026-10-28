<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = User::with('roles:id,name')
            ->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleName = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $users = $query->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($users, 'Daftar pengguna admin berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        setPermissionsTeamId($companyId);

        $user = User::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        if (! empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return $this->successResponse($user->load('roles:id,name'), 'Pengguna berhasil dibuat', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $user = User::with('roles:id,name')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($user, 'Detail pengguna berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $targetUser = User::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($targetUser->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        setPermissionsTeamId($companyId);

        $updateData = [
            'name' => $validated['name'] ?? $targetUser->name,
            'email' => $validated['email'] ?? $targetUser->email,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $targetUser->phone,
        ];

        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $targetUser->update($updateData);

        if ($request->has('roles')) {
            $targetUser->syncRoles($validated['roles'] ?? []);
        }

        return $this->successResponse($targetUser->fresh('roles:id,name'), 'Pengguna berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $targetUser = User::where('company_id', $companyId)->findOrFail($id);

        if ($targetUser->id === $request->user()->id) {
            return $this->errorResponse('Anda tidak dapat menghapus akun Anda sendiri', 422);
        }

        $targetUser->delete();

        return $this->successResponse(null, 'Pengguna berhasil dihapus');
    }

    public function toggleStatus(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $targetUser = User::where('company_id', $companyId)->findOrFail($id);

        if ($targetUser->id === $request->user()->id) {
            return $this->errorResponse('Anda tidak dapat menonaktifkan akun Anda sendiri', 422);
        }

        $targetUser->update(['is_active' => ! $targetUser->is_active]);

        return $this->successResponse($targetUser, 'Status pengguna berhasil diubah');
    }

    public function roles(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $roles = Role::where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->orWhereNull('company_id');
        })
            ->where('name', '!=', 'super-admin')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return $this->successResponse($roles, 'Daftar role berhasil diambil');
    }
}
