<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Admin login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun dinonaktifkan. Silakan hubungi superadmin.',
            ], 403);
        }

        if ($user->company_id) {
            setPermissionsTeamId($user->company_id);
        }

        // Allow superadmin, or users with administrative roles
        $adminRoles = ['admin', 'super-admin', 'hr-manager', 'payroll-manager', 'manager'];
        $hasAdminAccess = $user->is_superadmin || $user->hasAnyRole($adminRoles);

        if (! $hasAdminAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki hak akses dashboard admin.',
            ], 403);
        }

        // Create Sanctum Token
        $token = $user->createToken('admin-dashboard')->plainTextToken;

        $roles = $user->roles->pluck('name');
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'is_superadmin' => (bool) $user->is_superadmin,
                    'roles' => $roles,
                    'permissions' => $permissions,
                ],
                'company' => $user->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'logo' => $user->company->logo ?? null,
                ] : null,
            ],
        ]);
    }

    /**
     * Get current authenticated admin
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->company_id) {
            setPermissionsTeamId($user->company_id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'is_superadmin' => (bool) $user->is_superadmin,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
                'company' => $user->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'logo' => $user->company->logo ?? null,
                ] : null,
            ],
        ]);
    }

    /**
     * Logout admin and revoke current token
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
