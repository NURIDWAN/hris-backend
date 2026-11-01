<?php

namespace App\Http\Controllers\Api\V1\Admin\Superadmin;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class CompanyController extends AdminBaseController
{
    /**
     * Pastikan hanya superadmin yang dapat mengakses endpoint ini.
     */
    protected function authorizeSuperAdmin(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return $this->errorResponse('Akses ditolak. Fitur ini hanya untuk Superadmin.', 403);
        }

        return null;
    }

    /**
     * Daftar seluruh perusahaan / tenant beserta ringkasan statistik.
     */
    public function index(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $query = Company::withCount(['employees', 'users']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($plan = $request->input('subscription_plan')) {
            $query->where('subscription_plan', $plan);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $companies = $query->orderBy('name', 'asc')->paginate($perPage);

        // Agregat metrik untuk dashboard superadmin
        $meta = [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_active', true)->count(),
            'inactive_companies' => Company::where('is_active', false)->count(),
            'total_employees_across_tenants' => Employee::count(),
            'plans_breakdown' => [
                'enterprise' => Company::where('subscription_plan', 'enterprise')->count(),
                'pro' => Company::where('subscription_plan', 'pro')->count(),
                'starter' => Company::where('subscription_plan', 'starter')->count(),
                'trial' => Company::where('subscription_plan', 'trial')->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Daftar perusahaan berhasil diambil',
            'data' => $companies->items(),
            'meta' => array_merge([
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ], $meta),
        ]);
    }

    /**
     * Opsi ringan daftar perusahaan untuk dropdown tenant switcher.
     */
    public function options(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $companies = Company::query()
            ->select(['id', 'name', 'slug', 'logo', 'is_active', 'subscription_plan', 'max_employees'])
            ->withCount('employees')
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse($companies, 'Daftar opsi perusahaan berhasil diambil');
    }

    /**
     * Mendaftarkan tenant baru dan inisialisasi akun admin awal.
     */
    public function store(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:companies,slug'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'subscription_plan' => ['required', 'string', 'in:trial,starter,pro,enterprise'],
            'subscription_ends_at' => ['nullable', 'date'],
            'max_employees' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string', 'max:50'],
            // Akun admin awal (opsional)
            'create_admin_account' => ['nullable', 'boolean'],
            'admin_name' => ['required_if:create_admin_account,true', 'nullable', 'string', 'max:255'],
            'admin_email' => ['required_if:create_admin_account,true', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required_if:create_admin_account,true', 'nullable', 'string', 'min:8'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $company = Company::create([
                'name' => $validated['name'],
                'slug' => ! empty($validated['slug']) ? Str::slug($validated['slug']) : Company::generateUniqueSlug($validated['name']),
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'website' => $validated['website'] ?? null,
                'npwp' => $validated['npwp'] ?? null,
                'subscription_plan' => $validated['subscription_plan'],
                'subscription_ends_at' => $validated['subscription_ends_at'] ?? now()->addMonth(),
                'max_employees' => $validated['max_employees'] ?? 50,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
                'timezone' => $validated['timezone'] ?? 'Asia/Jakarta',
            ]);

            // Jika memilih membuat akun admin perusahaan awal
            if ($request->boolean('create_admin_account') && ! empty($validated['admin_email'])) {
                setPermissionsTeamId($company->id);

                $teamColumn = config('permission.column_names.team_foreign_key', 'company_id');

                // Pastikan role admin tersedia untuk team / company ini
                $adminRole = Role::firstOrCreate([
                    'name' => 'admin',
                    $teamColumn => $company->id,
                    'guard_name' => 'web',
                ]);

                // Salin permission default jika role admin baru dibuat untuk perusahaan ini
                if ($adminRole->wasRecentlyCreated) {
                    $globalAdmin = Role::where('name', 'admin')->whereNull($teamColumn)->first();
                    if ($globalAdmin && $globalAdmin->permissions()->exists()) {
                        $adminRole->syncPermissions($globalAdmin->permissions);
                    }
                }

                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $validated['admin_name'],
                    'email' => $validated['admin_email'],
                    'password' => Hash::make($validated['admin_password']),
                    'is_active' => true,
                    'is_superadmin' => false,
                ]);

                $user->assignRole($adminRole);
            }

            return $this->successResponse(
                $company->loadCount(['employees', 'users']),
                'Perusahaan baru berhasil didaftarkan',
                201
            );
        });
    }

    /**
     * Detail satu perusahaan.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $company = Company::withCount(['employees', 'users', 'departments', 'positions', 'officeLocations'])
            ->findOrFail($id);

        return $this->successResponse($company, 'Detail perusahaan berhasil diambil');
    }

    /**
     * Memperbarui informasi perusahaan.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('companies', 'slug')->ignore($company->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'subscription_plan' => ['sometimes', 'required', 'string', 'in:trial,starter,pro,enterprise'],
            'subscription_ends_at' => ['nullable', 'date'],
            'max_employees' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        if (isset($validated['slug']) && ! empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $company->update($validated);

        return $this->successResponse(
            $company->fresh()->loadCount(['employees', 'users']),
            'Data perusahaan berhasil diperbarui'
        );
    }

    /**
     * Mengaktifkan / menonaktifkan status tenant.
     */
    public function toggleStatus(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $company = Company::findOrFail($id);

        $company->is_active = ! $company->is_active;
        $company->save();

        $statusText = $company->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->successResponse(
            $company,
            "Perusahaan {$company->name} berhasil {$statusText}"
        );
    }
}
