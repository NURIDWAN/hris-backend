<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OvertimeController extends Controller
{
    #[OA\Get(
        path: '/overtimes',
        summary: 'Daftar pengajuan lembur',
        description: 'Mendapatkan daftar pengajuan lembur karyawan dengan filter status dan tanggal',
        tags: ['Overtime'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter berdasarkan status',
                schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'cancelled'])
            ),
            new OA\Parameter(
                name: 'start_date',
                in: 'query',
                description: 'Tanggal awal filter',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'end_date',
                in: 'query',
                description: 'Tanggal akhir filter',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Nomor halaman',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar pengajuan lembur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-02-15'),
                                    new OA\Property(property: 'start_time', type: 'string', example: '18:00'),
                                    new OA\Property(property: 'end_time', type: 'string', example: '21:00'),
                                    new OA\Property(property: 'overtime_hours', type: 'number', format: 'float', example: 3.0),
                                    new OA\Property(property: 'overtime_type', type: 'string', enum: ['weekday', 'weekend', 'holiday']),
                                    new OA\Property(property: 'overtime_type_label', type: 'string', example: 'Hari Kerja'),
                                    new OA\Property(property: 'overtime_amount', type: 'number', format: 'float', example: 150000.0),
                                    new OA\Property(property: 'reason', type: 'string', example: 'Menyelesaikan deadline project'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'cancelled']),
                                    new OA\Property(property: 'status_label', type: 'string', example: 'Menunggu'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 42),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,approved,rejected,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        $query = OvertimeRequest::where('employee_id', $employee->id)
            ->orderByDesc('date');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $overtimes = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $overtimes->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'date' => $overtime->date->toDateString(),
                    'start_time' => $overtime->start_time?->format('H:i'),
                    'end_time' => $overtime->end_time?->format('H:i'),
                    'overtime_hours' => (float) $overtime->overtime_hours,
                    'overtime_type' => $overtime->overtime_type,
                    'overtime_type_label' => $overtime->overtime_type_label,
                    'overtime_amount' => (float) $overtime->overtime_amount,
                    'formatted_amount' => $overtime->formatted_overtime_amount,
                    'reason' => $overtime->reason,
                    'status' => $overtime->status,
                    'status_label' => $overtime->status_label,
                ];
            }),
            'meta' => [
                'current_page' => $overtimes->currentPage(),
                'last_page' => $overtimes->lastPage(),
                'per_page' => $overtimes->perPage(),
                'total' => $overtimes->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/overtimes',
        summary: 'Buat pengajuan lembur',
        description: 'Membuat pengajuan lembur baru dengan menghitung jam lembur dan estimasi upah lembur',
        tags: ['Overtime'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['date', 'start_time', 'end_time', 'reason'],
                    properties: [
                        new OA\Property(
                            property: 'date',
                            type: 'string',
                            format: 'date',
                            example: '2026-02-15',
                            description: 'Tanggal lembur'
                        ),
                        new OA\Property(
                            property: 'start_time',
                            type: 'string',
                            example: '18:00',
                            description: 'Jam mulai lembur (format HH:mm)'
                        ),
                        new OA\Property(
                            property: 'end_time',
                            type: 'string',
                            example: '21:00',
                            description: 'Jam selesai lembur (format HH:mm)'
                        ),
                        new OA\Property(
                            property: 'overtime_type',
                            type: 'string',
                            enum: ['weekday', 'weekend', 'holiday'],
                            example: 'weekday',
                            description: 'Jenis lembur (opsional, akan otomatis terdeteksi jika tidak diisi)'
                        ),
                        new OA\Property(
                            property: 'reason',
                            type: 'string',
                            example: 'Menyelesaikan deadline project urgent',
                            maxLength: 500,
                            description: 'Alasan lembur'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pengajuan lembur berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengajuan lembur berhasil dibuat.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-02-15'),
                                new OA\Property(property: 'start_time', type: 'string', example: '18:00'),
                                new OA\Property(property: 'end_time', type: 'string', example: '21:00'),
                                new OA\Property(property: 'overtime_hours', type: 'number', format: 'float', example: 3.0),
                                new OA\Property(property: 'overtime_type', type: 'string', example: 'weekday'),
                                new OA\Property(property: 'overtime_amount', type: 'number', format: 'float', example: 150000.0),
                                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error atau sudah ada pengajuan pada tanggal tersebut',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Sudah ada pengajuan lembur pada tanggal tersebut.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'overtime_type' => 'nullable|string|in:weekday,weekend,holiday',
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $employee = $user->employee;
        $company = $user->company;

        // Check for existing request on same date (unique constraint prevents duplicates)
        $existing = OvertimeRequest::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada pengajuan lembur pada tanggal tersebut.',
            ], 422);
        }

        // Calculate overtime hours
        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);
        $overtimeHours = round($startTime->diffInMinutes($endTime) / 60, 2);

        // Get overtime settings
        $setting = OvertimeSetting::where('company_id', $company->id)->first();

        // Determine overtime type if not provided
        $overtimeType = $request->overtime_type ?? OvertimeRequest::TYPE_WEEKDAY;

        // Calculate overtime amount
        $overtimeAmount = 0;
        if ($setting && $employee->base_salary) {
            // Calculate hourly rate (assuming 173 working hours per month)
            $hourlyRate = $employee->base_salary / 173;

            $multiplier = match ($overtimeType) {
                OvertimeRequest::TYPE_WEEKEND => $setting->weekend_rate ?? 2.0,
                OvertimeRequest::TYPE_HOLIDAY => $setting->holiday_rate ?? 3.0,
                default => $setting->weekday_rate_first_hour ?? 1.5,
            };

            $overtimeAmount = $hourlyRate * $overtimeHours * $multiplier;
        }

        // Create overtime request
        $overtime = OvertimeRequest::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'overtime_hours' => $overtimeHours,
            'overtime_type' => $overtimeType,
            'overtime_amount' => $overtimeAmount,
            'reason' => $request->reason,
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dibuat.',
            'data' => [
                'id' => $overtime->id,
                'date' => $overtime->date->toDateString(),
                'start_time' => $overtime->start_time->format('H:i'),
                'end_time' => $overtime->end_time->format('H:i'),
                'overtime_hours' => (float) $overtime->overtime_hours,
                'overtime_type' => $overtime->overtime_type,
                'overtime_amount' => (float) $overtime->overtime_amount,
                'status' => $overtime->status,
            ],
        ], 201);
    }

    #[OA\Get(
        path: '/overtimes/{id}',
        summary: 'Detail pengajuan lembur',
        description: 'Mendapatkan detail lengkap pengajuan lembur termasuk informasi approval',
        tags: ['Overtime'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID pengajuan lembur',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail pengajuan lembur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-02-15'),
                                new OA\Property(property: 'start_time', type: 'string', example: '18:00'),
                                new OA\Property(property: 'end_time', type: 'string', example: '21:00'),
                                new OA\Property(property: 'overtime_hours', type: 'number', format: 'float', example: 3.0),
                                new OA\Property(property: 'overtime_type', type: 'string', example: 'weekday'),
                                new OA\Property(property: 'overtime_type_label', type: 'string', example: 'Hari Kerja'),
                                new OA\Property(property: 'overtime_amount', type: 'number', format: 'float', example: 150000.0),
                                new OA\Property(property: 'formatted_amount', type: 'string', example: 'Rp 150.000'),
                                new OA\Property(property: 'reason', type: 'string', example: 'Menyelesaikan deadline project'),
                                new OA\Property(property: 'status', type: 'string', example: 'approved'),
                                new OA\Property(property: 'status_label', type: 'string', example: 'Disetujui'),
                                new OA\Property(property: 'approved_by', type: 'string', nullable: true, example: 'John Doe'),
                                new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'rejection_reason', type: 'string', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Data tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data tidak ditemukan.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function show(Request $request, OvertimeRequest $overtime): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if ($overtime->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        $overtime->load('approver');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $overtime->id,
                'date' => $overtime->date->toDateString(),
                'start_time' => $overtime->start_time?->format('H:i'),
                'end_time' => $overtime->end_time?->format('H:i'),
                'overtime_hours' => (float) $overtime->overtime_hours,
                'overtime_type' => $overtime->overtime_type,
                'overtime_type_label' => $overtime->overtime_type_label,
                'overtime_amount' => (float) $overtime->overtime_amount,
                'formatted_amount' => $overtime->formatted_overtime_amount,
                'reason' => $overtime->reason,
                'status' => $overtime->status,
                'status_label' => $overtime->status_label,
                'approved_by' => $overtime->approver?->name,
                'approved_at' => $overtime->approved_at?->toDateTimeString(),
                'rejection_reason' => $overtime->rejection_reason,
                'created_at' => $overtime->created_at->toDateTimeString(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/overtimes/{id}/cancel',
        summary: 'Batalkan pengajuan lembur',
        description: 'Membatalkan pengajuan lembur dengan status pending',
        tags: ['Overtime'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID pengajuan lembur',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pengajuan berhasil dibatalkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengajuan lembur berhasil dibatalkan.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Data tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data tidak ditemukan.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Tidak dapat dibatalkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Hanya pengajuan dengan status menunggu yang dapat dibatalkan.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function cancel(Request $request, OvertimeRequest $overtime): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if ($overtime->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        if (! $overtime->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan dengan status menunggu yang dapat dibatalkan.',
            ], 422);
        }

        $overtime->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lembur berhasil dibatalkan.',
        ]);
    }

    #[OA\Get(
        path: '/overtimes/summary',
        summary: 'Ringkasan lembur bulanan',
        description: 'Mendapatkan ringkasan statistik lembur karyawan untuk bulan tertentu',
        tags: ['Overtime'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'month',
                in: 'query',
                required: true,
                description: 'Bulan (1-12)',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12, example: 2)
            ),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                required: true,
                description: 'Tahun',
                schema: new OA\Schema(type: 'integer', example: 2026)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ringkasan lembur bulanan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'total_requests',
                                    type: 'integer',
                                    example: 8,
                                    description: 'Total pengajuan lembur'
                                ),
                                new OA\Property(
                                    property: 'approved_requests',
                                    type: 'integer',
                                    example: 6,
                                    description: 'Jumlah pengajuan disetujui'
                                ),
                                new OA\Property(
                                    property: 'pending_requests',
                                    type: 'integer',
                                    example: 2,
                                    description: 'Jumlah pengajuan menunggu'
                                ),
                                new OA\Property(
                                    property: 'total_hours',
                                    type: 'number',
                                    format: 'float',
                                    example: 24.5,
                                    description: 'Total jam lembur yang disetujui'
                                ),
                                new OA\Property(
                                    property: 'total_amount',
                                    type: 'number',
                                    format: 'float',
                                    example: 1250000.0,
                                    description: 'Total upah lembur yang disetujui'
                                ),
                                new OA\Property(
                                    property: 'formatted_total_amount',
                                    type: 'string',
                                    example: 'Rp 1.250.000',
                                    description: 'Total upah lembur dalam format Rupiah'
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
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        $overtimes = OvertimeRequest::where('employee_id', $employee->id)
            ->whereYear('date', $request->year)
            ->whereMonth('date', $request->month)
            ->get();

        $approved = $overtimes->where('status', OvertimeRequest::STATUS_APPROVED);

        return response()->json([
            'success' => true,
            'data' => [
                'total_requests' => $overtimes->count(),
                'approved_requests' => $approved->count(),
                'pending_requests' => $overtimes->where('status', OvertimeRequest::STATUS_PENDING)->count(),
                'total_hours' => (float) $approved->sum('overtime_hours'),
                'total_amount' => (float) $approved->sum('overtime_amount'),
                'formatted_total_amount' => 'Rp '.number_format($approved->sum('overtime_amount'), 0, ',', '.'),
            ],
        ]);
    }
}
