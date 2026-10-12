<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/auth/demo-register',
        summary: 'Registrasi akun demo',
        description: 'Mendaftarkan akun baru sebagai karyawan PT Demo GajiPro untuk testing aplikasi',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registrasi berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Registrasi berhasil. Silakan login dengan email dan password Anda.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Email sudah terdaftar.'),
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: 'Demo company tidak tersedia',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Demo company tidak tersedia. Silakan hubungi administrator.'),
                    ]
                )
            ),
        ]
    )]
    public function demoRegister(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => 'Email sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        // Get demo company
        $company = Company::where('slug', 'demo-gajipro')->first();

        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Demo company tidak tersedia. Silakan hubungi administrator.',
            ], 503);
        }

        try {
            $result = DB::transaction(function () use ($request, $company) {
                // Get default department, position, work schedule, and office
                $department = Department::where('company_id', $company->id)
                    ->where('code', 'ENG')
                    ->first() ?? Department::where('company_id', $company->id)->first();

                $position = Position::where('company_id', $company->id)
                    ->where('code', 'STF')
                    ->first() ?? Position::where('company_id', $company->id)->first();

                $workSchedule = WorkSchedule::where('company_id', $company->id)
                    ->where('is_default', true)
                    ->first() ?? WorkSchedule::where('company_id', $company->id)->first();

                $officeLocation = OfficeLocation::where('company_id', $company->id)
                    ->where('is_active', true)
                    ->first();

                if (! $department || ! $position || ! $workSchedule || ! $officeLocation) {
                    throw new \Exception('Demo company belum dikonfigurasi dengan benar.');
                }

                // Generate unique employee ID
                $lastEmployee = Employee::where('company_id', $company->id)
                    ->where('employee_id', 'like', 'DEMO'.date('Y').'%')
                    ->orderBy('employee_id', 'desc')
                    ->first();

                $nextNumber = 1;
                if ($lastEmployee) {
                    $lastNumber = (int) substr($lastEmployee->employee_id, -3);
                    $nextNumber = $lastNumber + 1;
                }
                $employeeId = 'DEMO'.date('Y').str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                // Parse name
                $nameParts = explode(' ', trim($request->name), 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                // Create user
                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                // Assign employee role
                $user->assignRole('employee');

                // Create employee record
                $employee = Employee::create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'department_id' => $department->id,
                    'position_id' => $position->id,
                    'work_schedule_id' => $workSchedule->id,
                    'employee_id' => $employeeId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'hire_date' => now(),
                    'employment_status' => 'permanent',
                    'is_active' => true,
                ]);

                // Assign office location
                $employee->officeLocations()->attach($officeLocation->id, ['is_primary' => true]);

                return [
                    'email' => $user->email,
                    'name' => $user->name,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil. Silakan login dengan email dan password Anda.',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Login ke aplikasi',
        description: 'Autentikasi pengguna dengan email atau ID karyawan dan password, mengembalikan token Sanctum',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'karyawan@perusahaan.com', description: 'Email atau ID Karyawan'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456...'),
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                                        new OA\Property(property: 'email', type: 'string', example: 'john@company.com'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'employee',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'employee_id', type: 'string', example: 'EMP20260001'),
                                        new OA\Property(property: 'full_name', type: 'string', example: 'John Doe'),
                                        new OA\Property(property: 'department', type: 'string', example: 'IT'),
                                        new OA\Property(property: 'position', type: 'string', example: 'Software Engineer'),
                                        new OA\Property(property: 'face_enrolled', type: 'boolean', example: true, description: 'Apakah wajah sudah didaftarkan'),
                                        new OA\Property(
                                            property: 'face_embedding',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Data embedding wajah untuk verifikasi di client menggunakan TFLite/Google MLKit (null jika belum enrolled atau face recognition disabled)',
                                            properties: [
                                                new OA\Property(property: 'model', type: 'string', example: 'google_mlkit', description: 'Model yang digunakan untuk generate embedding (google_mlkit, tflite)'),
                                                new OA\Property(property: 'version', type: 'string', example: '1.0', description: 'Versi format embedding'),
                                                new OA\Property(
                                                    property: 'embedding',
                                                    type: 'array',
                                                    items: new OA\Items(type: 'number', format: 'float'),
                                                    description: 'Array 128/512 nilai float embedding wajah dari TFLite/MLKit'
                                                ),
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'assigned_offices',
                                            type: 'array',
                                            description: 'Daftar lokasi kantor yang di-assign ke karyawan untuk validasi GPS di client',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Kantor Pusat'),
                                                    new OA\Property(property: 'code', type: 'string', example: 'HQ-001'),
                                                    new OA\Property(property: 'latitude', type: 'number', format: 'double', example: -6.200000),
                                                    new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.816666),
                                                    new OA\Property(property: 'radius', type: 'integer', example: 100, description: 'Radius validasi GPS dalam meter'),
                                                    new OA\Property(property: 'is_primary', type: 'boolean', example: true),
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'company',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'PT Example'),
                                        new OA\Property(property: 'enable_face_recognition', type: 'boolean', example: true, description: 'Apakah face recognition diaktifkan'),
                                        new OA\Property(property: 'face_match_threshold', type: 'number', format: 'float', example: 0.6, description: 'Threshold kecocokan wajah (0-1)'),
                                        new OA\Property(property: 'enable_gps_validation', type: 'boolean', example: true, description: 'Apakah validasi GPS diaktifkan'),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Kredensial salah',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Email/ID Karyawan atau password salah.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Akun tidak terdaftar sebagai karyawan atau tidak aktif',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Akun ini tidak terdaftar sebagai karyawan.'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = $request->email;
        $user = null;

        // 1. Coba cari berdasarkan email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $identifier)->first();

            // Verifikasi password jika user ditemukan via email
            if ($user && ! Hash::check($request->password, $user->password)) {
                $user = null; // Reset jika password tidak cocok, lanjut coba cari via ID Karyawan
            }
        }

        // 2. Coba cari berdasarkan ID Karyawan jika belum ditemukan via email
        if (! $user) {
            $employees = Employee::where('employee_id', $identifier)
                ->get();

            foreach ($employees as $employee) {
                if ($employee->user && Hash::check($request->password, $employee->user->password)) {
                    $user = $employee->user;
                    break;
                }
            }
        }

        // 3. Jika tetap tidak ditemukan, cek apakah ini akun yang baru saja di-soft delete
        if (! $user) {
            $deletedUser = User::withTrashed()
                ->where('email', $identifier)
                ->whereNotNull('deleted_at')
                ->first();

            if ($deletedUser && Hash::check($request->password, $deletedUser->password)) {
                $deletedAt = $deletedUser->deleted_at;
                $daysLeft = 30 - $deletedAt->diffInDays(now());

                if ($daysLeft > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Akun Anda telah dihapus. Anda memiliki {$daysLeft} hari untuk mengaktifkan kembali akun dengan menghubungi admin.",
                        'data' => [
                            'deleted_at' => $deletedAt->toDateTimeString(),
                            'can_reactivate' => true,
                            'days_left' => $daysLeft,
                        ],
                    ], 403);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akun Anda telah dihapus permanen dan tidak dapat diaktifkan kembali.',
                        'data' => [
                            'can_reactivate' => false,
                        ],
                    ], 403);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Email/ID Karyawan atau password salah.',
            ], 401);
        }

        // 4. Verifikasi status karyawan
        $employee = $user->employee;
        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini tidak terdaftar sebagai karyawan.',
            ], 403);
        }

        // Check if employee is active
        if (! $employee->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun karyawan tidak aktif.',
            ], 403);
        }

        // Revoke existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('mobile-app')->plainTextToken;

        $company = $user->company;

        // Get face embedding data if face recognition is enabled
        $faceEnrolled = false;
        $faceEmbedding = null;
        if ($company->enable_face_recognition) {
            $faceEmbeddingRecord = $employee->faceEmbedding()->where('is_active', true)->first();
            $faceEnrolled = $faceEmbeddingRecord !== null;
            if ($faceEnrolled) {
                $faceEmbedding = $faceEmbeddingRecord->embedding_data;
            }
        }

        // Get assigned offices for client-side GPS validation
        $assignedOffices = $employee->officeLocations()
            ->where('is_active', true)
            ->get()
            ->map(fn ($office) => [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
                'latitude' => $office->latitude,
                'longitude' => $office->longitude,
                'radius' => $office->radius,
                'is_primary' => (bool) $office->pivot->is_primary,
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'full_name' => $employee->full_name,
                    'department' => $employee->department?->name,
                    'position' => $employee->position?->name,
                    'face_enrolled' => $faceEnrolled,
                    'face_embedding' => $faceEmbedding,
                    'assigned_offices' => $assignedOffices,
                ],
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'enable_face_recognition' => (bool) $company->enable_face_recognition,
                    'face_match_threshold' => $company->face_match_threshold ?? 0.6,
                    'enable_gps_validation' => (bool) $company->enable_gps_validation,
                ],
            ],
        ]);
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout dari aplikasi',
        description: 'Menghapus token akses saat ini',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Berhasil logout.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout.',
        ]);
    }

    #[OA\Get(
        path: '/auth/profile',
        summary: 'Mendapatkan profil pengguna',
        description: 'Mengembalikan data profil pengguna yang sedang login beserta data karyawan dan perusahaan',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data profil berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'email', type: 'string'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'employee',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'employee_id', type: 'string'),
                                        new OA\Property(property: 'full_name', type: 'string'),
                                        new OA\Property(property: 'first_name', type: 'string'),
                                        new OA\Property(property: 'last_name', type: 'string'),
                                        new OA\Property(property: 'phone', type: 'string', nullable: true),
                                        new OA\Property(property: 'photo', type: 'string', nullable: true),
                                        new OA\Property(property: 'department', type: 'string', nullable: true),
                                        new OA\Property(property: 'position', type: 'string', nullable: true),
                                        new OA\Property(property: 'hire_date', type: 'string', format: 'date'),
                                        new OA\Property(property: 'employment_status', type: 'string'),
                                        new OA\Property(property: 'face_enrolled', type: 'boolean', description: 'Apakah wajah sudah didaftarkan'),
                                        new OA\Property(
                                            property: 'face_embedding',
                                            type: 'object',
                                            nullable: true,
                                            description: 'Data embedding wajah untuk verifikasi di client menggunakan TFLite/Google MLKit',
                                            properties: [
                                                new OA\Property(property: 'model', type: 'string', description: 'Model: google_mlkit atau tflite'),
                                                new OA\Property(property: 'version', type: 'string', description: 'Versi format embedding'),
                                                new OA\Property(property: 'embedding', type: 'array', items: new OA\Items(type: 'number'), description: 'Array float embedding'),
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'assigned_offices',
                                            type: 'array',
                                            description: 'Daftar lokasi kantor yang di-assign ke karyawan untuk validasi GPS di client',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer'),
                                                    new OA\Property(property: 'name', type: 'string'),
                                                    new OA\Property(property: 'code', type: 'string'),
                                                    new OA\Property(property: 'latitude', type: 'number', format: 'double'),
                                                    new OA\Property(property: 'longitude', type: 'number', format: 'double'),
                                                    new OA\Property(property: 'radius', type: 'integer', description: 'Radius dalam meter'),
                                                    new OA\Property(property: 'is_primary', type: 'boolean'),
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'company',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'logo', type: 'string', nullable: true),
                                        new OA\Property(property: 'enable_face_recognition', type: 'boolean'),
                                        new OA\Property(property: 'face_match_threshold', type: 'number', format: 'float'),
                                        new OA\Property(property: 'enable_gps_validation', type: 'boolean'),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        $company = $user->company;

        // Get face embedding data if face recognition is enabled
        $faceEnrolled = false;
        $faceEmbedding = null;
        if ($company->enable_face_recognition) {
            $faceEmbeddingRecord = $employee->faceEmbedding()->where('is_active', true)->first();
            $faceEnrolled = $faceEmbeddingRecord !== null;
            if ($faceEnrolled) {
                $faceEmbedding = $faceEmbeddingRecord->embedding_data;
            }
        }

        // Get assigned offices for client-side GPS validation
        $assignedOffices = $employee->officeLocations()
            ->where('is_active', true)
            ->get()
            ->map(fn ($office) => [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
                'latitude' => $office->latitude,
                'longitude' => $office->longitude,
                'radius' => $office->radius,
                'is_primary' => (bool) $office->pivot->is_primary,
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'full_name' => $employee->full_name,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'phone' => $employee->phone,
                    'photo' => $employee->photo ? asset('storage/'.$employee->photo) : null,
                    'department' => $employee->department?->name,
                    'position' => $employee->position?->name,
                    'hire_date' => $employee->hire_date?->toDateString(),
                    'employment_status' => $employee->employment_status,
                    'face_enrolled' => $faceEnrolled,
                    'face_embedding' => $faceEmbedding,
                    'assigned_offices' => $assignedOffices,
                ],
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo' => $company->logo ? asset('storage/'.$company->logo) : null,
                    'enable_face_recognition' => (bool) $company->enable_face_recognition,
                    'face_match_threshold' => $company->face_match_threshold ?? 0.6,
                    'enable_gps_validation' => (bool) $company->enable_gps_validation,
                ],
            ],
        ]);
    }

    #[OA\Post(
        path: '/auth/change-password',
        summary: 'Mengubah password',
        description: 'Mengubah password pengguna yang sedang login',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', example: 'oldpassword'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'newpassword123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'newpassword123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password berhasil diubah',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Password berhasil diubah.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }

    #[OA\Delete(
        path: '/auth/delete-account',
        summary: 'Hapus akun',
        description: 'Menghapus akun pengguna (soft delete). Akun dapat direaktivasi dalam 30 hari dengan menghubungi admin.',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password', 'confirmation'],
                properties: [
                    new OA\Property(property: 'password', type: 'string', example: 'password123', description: 'Password saat ini untuk konfirmasi'),
                    new OA\Property(property: 'confirmation', type: 'string', example: 'HAPUS AKUN', description: 'Ketik "HAPUS AKUN" untuk konfirmasi'),
                    new OA\Property(property: 'reason', type: 'string', example: 'Tidak lagi bekerja di perusahaan ini', description: 'Alasan penghapusan akun (opsional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Akun berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Akun Anda berhasil dihapus. Akun dapat direaktivasi dalam 30 hari dengan menghubungi admin.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Password salah',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Password tidak sesuai.'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|string|in:HAPUS AKUN',
            'reason' => 'nullable|string|max:500',
        ], [
            'confirmation.in' => 'Ketik "HAPUS AKUN" untuk konfirmasi penghapusan.',
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak sesuai.',
            ], 401);
        }

        // Store deletion reason if provided
        if ($request->reason) {
            $user->update(['deletion_reason' => $request->reason]);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Deactivate employee record
        if ($user->employee) {
            $user->employee->update(['is_active' => false]);
        }

        // Soft delete the user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun Anda berhasil dihapus. Akun dapat direaktivasi dalam 30 hari dengan menghubungi admin.',
        ]);
    }

    #[OA\Patch(
        path: '/auth/profile',
        summary: 'Update profil',
        description: 'Memperbarui data profil karyawan termasuk nama, telepon, alamat, dan foto',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'first_name', type: 'string', maxLength: 255, example: 'John', description: 'Nama depan'),
                        new OA\Property(property: 'last_name', type: 'string', maxLength: 255, example: 'Doe', description: 'Nama belakang'),
                        new OA\Property(property: 'phone', type: 'string', example: '081234567890', description: 'Nomor telepon (format Indonesia)'),
                        new OA\Property(property: 'address', type: 'string', example: 'Jl. Sudirman No. 1', description: 'Alamat'),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'Foto profil (jpg, jpeg, png, max 2MB)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Profil berhasil diperbarui.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'employee',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'employee_id', type: 'string'),
                                        new OA\Property(property: 'full_name', type: 'string'),
                                        new OA\Property(property: 'first_name', type: 'string'),
                                        new OA\Property(property: 'last_name', type: 'string'),
                                        new OA\Property(property: 'phone', type: 'string', nullable: true),
                                        new OA\Property(property: 'photo', type: 'string', nullable: true),
                                        new OA\Property(property: 'address', type: 'string', nullable: true),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s]+$/', 'min:10', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($employee->photo) {
                \Storage::disk('public')->delete($employee->photo);
            }

            $validated['photo'] = $request->file('photo')->store(
                'employee-photos/'.$user->company_id,
                'public'
            );
        }

        // Filter out null values to only update provided fields
        $updateData = array_filter($validated, fn ($value) => $value !== null);

        if (! empty($updateData)) {
            $employee->update($updateData);

            // Sync user name if first_name or last_name updated
            if (isset($updateData['first_name']) || isset($updateData['last_name'])) {
                $employee->refresh();
                $user->update(['name' => $employee->full_name]);
            }
        }

        $employee->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'full_name' => $employee->full_name,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'phone' => $employee->phone,
                    'photo' => $employee->photo ? asset('storage/'.$employee->photo) : null,
                    'address' => $employee->address,
                ],
            ],
        ]);
    }
}
