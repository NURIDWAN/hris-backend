<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\OfficeLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EmployeeOfficeController extends Controller
{
    /**
     * Check if user has HR or Admin role.
     */
    protected function isHrOrAdmin(Request $request): bool
    {
        $user = $request->user();

        return $user->hasAnyRole(['super-admin', 'admin', 'hr-manager']);
    }

    /**
     * Check if user can access employee data.
     */
    protected function canAccessEmployee(Request $request, Employee $employee): bool
    {
        $user = $request->user();

        // HR/Admin can access any employee in their company
        if ($this->isHrOrAdmin($request)) {
            return $employee->company_id === $user->company_id;
        }

        // Regular employee can only access their own data
        return $user->employee && $user->employee->id === $employee->id;
    }

    #[OA\Get(
        path: '/employees/{employee}/offices',
        summary: 'Daftar Kantor Karyawan',
        description: 'Mengambil daftar lokasi kantor yang di-assign ke karyawan. HR/Admin dapat melihat data karyawan manapun, karyawan hanya bisa melihat data sendiri.',
        tags: ['EmployeeOffice'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'employee',
                in: 'path',
                required: true,
                description: 'ID Employee',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar lokasi kantor berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Kantor Pusat'),
                                    new OA\Property(property: 'code', type: 'string', example: 'HQ-001'),
                                    new OA\Property(property: 'address', type: 'string', example: 'Jl. Sudirman No. 1'),
                                    new OA\Property(property: 'latitude', type: 'number', format: 'float', example: -6.200000),
                                    new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 106.816666),
                                    new OA\Property(property: 'radius', type: 'integer', example: 100),
                                    new OA\Property(property: 'is_primary', type: 'boolean', example: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - tidak memiliki akses'),
            new OA\Response(response: 404, description: 'Employee tidak ditemukan'),
        ]
    )]
    public function index(Request $request, int $employeeId): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('company_id', $user->company_id)
            ->find($employeeId);

        if (! $employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan.',
            ], 404);
        }

        if (! $this->canAccessEmployee($request, $employee)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melihat data ini.',
            ], 403);
        }

        $offices = $employee->officeLocations()
            ->where('is_active', true)
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'name' => $office->name,
                    'code' => $office->code,
                    'address' => $office->address,
                    'latitude' => $office->latitude,
                    'longitude' => $office->longitude,
                    'radius' => $office->radius,
                    'is_primary' => (bool) $office->pivot->is_primary,
                ];
            });

        return response()->json([
            'data' => $offices,
        ]);
    }

    #[OA\Post(
        path: '/employees/{employee}/offices',
        summary: 'Assign Kantor ke Karyawan',
        description: 'Meng-assign satu atau lebih lokasi kantor ke karyawan. Hanya HR/Admin yang dapat melakukan aksi ini. Assignment sebelumnya akan diganti.',
        tags: ['EmployeeOffice'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'employee',
                in: 'path',
                required: true,
                description: 'ID Employee',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['office_location_ids'],
                properties: [
                    new OA\Property(
                        property: 'office_location_ids',
                        type: 'array',
                        description: 'Array ID lokasi kantor',
                        items: new OA\Items(type: 'integer'),
                        example: [1, 2, 3]
                    ),
                    new OA\Property(
                        property: 'primary_office_id',
                        type: 'integer',
                        description: 'ID kantor utama (opsional, default ke office pertama)',
                        example: 1,
                        nullable: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Office locations berhasil di-assign',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Office locations berhasil di-assign.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - hanya HR/Admin yang dapat assign'),
            new OA\Response(response: 404, description: 'Employee tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request, int $employeeId): JsonResponse
    {
        $user = $request->user();

        if (! $this->isHrOrAdmin($request)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini.',
            ], 403);
        }

        $employee = Employee::where('company_id', $user->company_id)
            ->find($employeeId);

        if (! $employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'office_location_ids' => ['required', 'array', 'min:1'],
            'office_location_ids.*' => [
                'required',
                'integer',
                'exists:office_locations,id',
                function ($attribute, $value, $fail) use ($user) {
                    $office = OfficeLocation::find($value);
                    if ($office && $office->company_id !== $user->company_id) {
                        $fail('Office location tidak valid.');
                    }
                },
            ],
            'primary_office_id' => ['nullable', 'integer'],
        ]);

        $officeIds = $validated['office_location_ids'];
        $primaryOfficeId = $validated['primary_office_id'] ?? $officeIds[0];

        // Validate primary_office_id is in office_location_ids
        if (! in_array($primaryOfficeId, $officeIds)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'primary_office_id' => ['Primary office harus termasuk dalam office_location_ids.'],
                ],
            ], 422);
        }

        // Build sync data
        $syncData = [];
        foreach ($officeIds as $officeId) {
            $syncData[$officeId] = [
                'is_primary' => $officeId === $primaryOfficeId,
            ];
        }

        // Sync offices (replaces all existing)
        $employee->officeLocations()->sync($syncData);

        return response()->json([
            'message' => 'Office locations berhasil di-assign.',
        ]);
    }

    #[OA\Delete(
        path: '/employees/{employee}/offices',
        summary: 'Hapus Semua Assignment Kantor',
        description: 'Menghapus semua assignment lokasi kantor dari karyawan. Hanya HR/Admin yang dapat melakukan aksi ini.',
        tags: ['EmployeeOffice'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'employee',
                in: 'path',
                required: true,
                description: 'ID Employee',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Semua office locations berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Semua office locations berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - hanya HR/Admin yang dapat hapus'),
            new OA\Response(response: 404, description: 'Employee tidak ditemukan'),
        ]
    )]
    public function destroyAll(Request $request, int $employeeId): JsonResponse
    {
        $user = $request->user();

        if (! $this->isHrOrAdmin($request)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini.',
            ], 403);
        }

        $employee = Employee::where('company_id', $user->company_id)
            ->find($employeeId);

        if (! $employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan.',
            ], 404);
        }

        $employee->officeLocations()->detach();

        return response()->json([
            'message' => 'Semua office locations berhasil dihapus.',
        ]);
    }

    #[OA\Delete(
        path: '/employees/{employee}/offices/{office}',
        summary: 'Hapus Assignment Kantor Tertentu',
        description: 'Menghapus assignment satu lokasi kantor dari karyawan. Jika kantor yang dihapus adalah primary, kantor lain akan otomatis dijadikan primary.',
        tags: ['EmployeeOffice'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'employee',
                in: 'path',
                required: true,
                description: 'ID Employee',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'office',
                in: 'path',
                required: true,
                description: 'ID Office Location',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Office location berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Office location berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - hanya HR/Admin yang dapat hapus'),
            new OA\Response(response: 404, description: 'Employee atau office tidak ditemukan'),
        ]
    )]
    public function destroy(Request $request, int $employeeId, int $officeId): JsonResponse
    {
        $user = $request->user();

        if (! $this->isHrOrAdmin($request)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini.',
            ], 403);
        }

        $employee = Employee::where('company_id', $user->company_id)
            ->find($employeeId);

        if (! $employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan.',
            ], 404);
        }

        // Check if office is assigned to employee
        $assignment = $employee->officeLocations()->where('office_location_id', $officeId)->first();
        if (! $assignment) {
            return response()->json([
                'message' => 'Office location tidak ditemukan.',
            ], 404);
        }

        $wasPrimary = (bool) $assignment->pivot->is_primary;

        // Detach the office
        $employee->officeLocations()->detach($officeId);

        // If the removed office was primary, promote another one
        if ($wasPrimary) {
            $nextOffice = $employee->officeLocations()->first();
            if ($nextOffice) {
                $employee->officeLocations()->updateExistingPivot($nextOffice->id, [
                    'is_primary' => true,
                ]);
            }
        }

        return response()->json([
            'message' => 'Office location berhasil dihapus.',
        ]);
    }

    #[OA\Patch(
        path: '/employees/{employee}/offices/{office}/set-primary',
        summary: 'Set Kantor Sebagai Primary',
        description: 'Menjadikan lokasi kantor sebagai primary untuk karyawan. Kantor sebelumnya yang primary akan di-unset.',
        tags: ['EmployeeOffice'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'employee',
                in: 'path',
                required: true,
                description: 'ID Employee',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'office',
                in: 'path',
                required: true,
                description: 'ID Office Location',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Office location berhasil dijadikan primary',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Office location berhasil dijadikan primary.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - hanya HR/Admin yang dapat mengubah'),
            new OA\Response(response: 404, description: 'Office tidak di-assign ke karyawan'),
        ]
    )]
    public function setPrimary(Request $request, int $employeeId, int $officeId): JsonResponse
    {
        $user = $request->user();

        if (! $this->isHrOrAdmin($request)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini.',
            ], 403);
        }

        $employee = Employee::where('company_id', $user->company_id)
            ->find($employeeId);

        if (! $employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan.',
            ], 404);
        }

        // Check if office is assigned to employee
        $assignment = $employee->officeLocations()->where('office_location_id', $officeId)->first();
        if (! $assignment) {
            return response()->json([
                'message' => 'Office location tidak di-assign ke karyawan ini.',
            ], 404);
        }

        // Unset all as primary first using pivot table directly
        \DB::table('employee_office_locations')
            ->where('employee_id', $employee->id)
            ->update(['is_primary' => false]);

        // Set the specified office as primary
        $employee->officeLocations()->updateExistingPivot($officeId, [
            'is_primary' => true,
        ]);

        return response()->json([
            'message' => 'Office location berhasil dijadikan primary.',
        ]);
    }
}
