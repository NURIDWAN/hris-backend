<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OfficeLocation;
use App\Services\GpsValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OfficeLocationController extends Controller
{
    public function __construct(
        protected GpsValidationService $gpsValidationService
    ) {}

    #[OA\Get(
        path: '/office-locations',
        summary: 'Daftar Lokasi Kantor',
        description: 'Menampilkan daftar semua lokasi kantor yang aktif di perusahaan. Diurutkan berdasarkan nama.',
        tags: ['OfficeLocation'],
        security: [['sanctum' => []]],
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
                                    new OA\Property(property: 'name', type: 'string', example: 'Kantor Pusat Jakarta'),
                                    new OA\Property(property: 'code', type: 'string', example: 'HQ-JKT', nullable: true),
                                    new OA\Property(property: 'address', type: 'string', example: 'Jl. Sudirman No. 123'),
                                    new OA\Property(property: 'city', type: 'string', example: 'Jakarta Selatan', nullable: true),
                                    new OA\Property(property: 'province', type: 'string', example: 'DKI Jakarta', nullable: true),
                                    new OA\Property(property: 'latitude', type: 'number', format: 'double', example: -6.2088),
                                    new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.8456),
                                    new OA\Property(property: 'radius', type: 'integer', example: 100, description: 'Radius validasi GPS dalam meter'),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    new OA\Property(property: 'is_headquarters', type: 'boolean', example: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $officeLocations = OfficeLocation::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $officeLocations->map(fn ($office) => $this->formatOfficeLocation($office)),
        ]);
    }

    #[OA\Get(
        path: '/office-locations/assigned',
        summary: 'Lokasi Kantor yang Ditugaskan',
        description: 'Menampilkan daftar lokasi kantor yang ditugaskan kepada karyawan yang sedang login. Karyawan hanya dapat melakukan absensi di lokasi yang ditugaskan.',
        tags: ['OfficeLocation'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar lokasi kantor yang ditugaskan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Kantor Pusat Jakarta'),
                                    new OA\Property(property: 'code', type: 'string', example: 'HQ-JKT', nullable: true),
                                    new OA\Property(property: 'address', type: 'string', example: 'Jl. Sudirman No. 123'),
                                    new OA\Property(property: 'city', type: 'string', example: 'Jakarta Selatan', nullable: true),
                                    new OA\Property(property: 'province', type: 'string', example: 'DKI Jakarta', nullable: true),
                                    new OA\Property(property: 'latitude', type: 'number', format: 'double', example: -6.2088),
                                    new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.8456),
                                    new OA\Property(property: 'radius', type: 'integer', example: 100),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    new OA\Property(property: 'is_headquarters', type: 'boolean', example: true),
                                    new OA\Property(property: 'is_primary', type: 'boolean', example: true, description: 'Apakah ini lokasi utama karyawan'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Employee tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Employee not found for this user.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function assigned(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found for this user.',
            ], 404);
        }

        $officeLocations = $employee->officeLocations()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $officeLocations->map(fn ($office) => $this->formatOfficeLocation($office, $office->pivot)),
        ]);
    }

    #[OA\Get(
        path: '/office-locations/{id}',
        summary: 'Detail Lokasi Kantor',
        description: 'Menampilkan detail lokasi kantor berdasarkan ID.',
        tags: ['OfficeLocation'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID lokasi kantor',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail lokasi kantor berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Kantor Pusat Jakarta'),
                                new OA\Property(property: 'code', type: 'string', example: 'HQ-JKT', nullable: true),
                                new OA\Property(property: 'address', type: 'string', example: 'Jl. Sudirman No. 123'),
                                new OA\Property(property: 'city', type: 'string', example: 'Jakarta Selatan', nullable: true),
                                new OA\Property(property: 'province', type: 'string', example: 'DKI Jakarta', nullable: true),
                                new OA\Property(property: 'latitude', type: 'number', format: 'double', example: -6.2088),
                                new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.8456),
                                new OA\Property(property: 'radius', type: 'integer', example: 100),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'is_headquarters', type: 'boolean', example: true),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Lokasi kantor tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Office location not found.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $officeLocation = OfficeLocation::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (! $officeLocation) {
            return response()->json([
                'message' => 'Office location not found.',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatOfficeLocation($officeLocation),
        ]);
    }

    #[OA\Post(
        path: '/office-locations/validate-gps',
        summary: 'Validasi GPS',
        description: 'Memvalidasi koordinat GPS karyawan terhadap lokasi kantor yang ditugaskan. Mengembalikan apakah lokasi valid untuk absensi dan jarak ke kantor terdekat.',
        tags: ['OfficeLocation'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['latitude', 'longitude'],
                properties: [
                    new OA\Property(property: 'latitude', type: 'number', format: 'double', example: -6.2088, description: 'Latitude koordinat GPS (-90 sampai 90)'),
                    new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.8456, description: 'Longitude koordinat GPS (-180 sampai 180)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Hasil validasi GPS',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'valid', type: 'boolean', example: true, description: 'Apakah lokasi valid untuk absensi'),
                                new OA\Property(property: 'office_location_id', type: 'integer', example: 1, nullable: true, description: 'ID lokasi kantor terdekat yang valid'),
                                new OA\Property(property: 'distance', type: 'number', format: 'float', example: 45.5, nullable: true, description: 'Jarak ke kantor terdekat dalam meter'),
                                new OA\Property(property: 'reason', type: 'string', example: 'outside_radius', nullable: true, description: 'Alasan jika tidak valid (no_assigned_offices, no_active_offices, outside_radius)'),
                                new OA\Property(
                                    property: 'office',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Kantor Pusat Jakarta'),
                                        new OA\Property(property: 'address', type: 'string', example: 'Jl. Sudirman No. 123'),
                                        new OA\Property(property: 'latitude', type: 'number', format: 'double', example: -6.2088),
                                        new OA\Property(property: 'longitude', type: 'number', format: 'double', example: 106.8456),
                                        new OA\Property(property: 'radius', type: 'integer', example: 100),
                                    ],
                                    type: 'object',
                                    description: 'Detail lokasi kantor jika valid'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Employee tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Employee not found for this user.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function validateGps(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found for this user.',
            ], 404);
        }

        $result = $this->gpsValidationService->validateEmployeeLocation(
            $employee,
            $validated['latitude'],
            $validated['longitude']
        );

        $response = [
            'valid' => $result['valid'],
            'office_location_id' => $result['office_location_id'],
            'distance' => $result['distance'],
        ];

        if (! $result['valid']) {
            $response['reason'] = $result['reason'];
        }

        // If valid, include office details
        if ($result['valid'] && $result['office_location_id']) {
            $office = OfficeLocation::find($result['office_location_id']);
            $response['office'] = $office ? $this->formatOfficeLocation($office) : null;
        }

        return response()->json([
            'data' => $response,
        ]);
    }

    /**
     * Format office location for API response.
     */
    protected function formatOfficeLocation(OfficeLocation $office, $pivot = null): array
    {
        $data = [
            'id' => $office->id,
            'name' => $office->name,
            'code' => $office->code,
            'address' => $office->address,
            'city' => $office->city,
            'province' => $office->province,
            'latitude' => $office->latitude,
            'longitude' => $office->longitude,
            'radius' => $office->radius,
            'is_active' => $office->is_active,
            'is_headquarters' => $office->is_headquarters,
        ];

        if ($pivot) {
            $data['is_primary'] = (bool) $pivot->is_primary;
        }

        return $data;
    }
}
