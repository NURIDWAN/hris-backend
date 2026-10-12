<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApprovalWorkflow;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LeaveController extends Controller
{
    #[OA\Get(
        path: '/leaves',
        summary: 'Mendapatkan daftar pengajuan cuti',
        description: 'Mengembalikan daftar semua pengajuan cuti karyawan dengan filter status dan tahun',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'cancelled']),
                description: 'Filter berdasarkan status cuti'
            ),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                schema: new OA\Schema(type: 'integer'),
                description: 'Filter berdasarkan tahun (default: tahun berjalan)'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 1),
                description: 'Nomor halaman untuk pagination'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar pengajuan cuti berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'request_number', type: 'string', example: 'LV20260001'),
                                    new OA\Property(property: 'leave_type', type: 'string', example: 'Cuti Tahunan'),
                                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-03-01'),
                                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-03-05'),
                                    new OA\Property(property: 'total_days', type: 'number', format: 'float', example: 5.0),
                                    new OA\Property(property: 'reason', type: 'string', example: 'Liburan keluarga'),
                                    new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                    new OA\Property(property: 'status_label', type: 'string', example: 'Menunggu Persetujuan'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
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
            'year' => 'nullable|integer',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        $query = LeaveRequest::with(['leaveType', 'approvedBy'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->year) {
            $query->whereYear('start_date', $request->year);
        }

        $leaves = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'request_number' => $leave->request_number,
                    'leave_type' => [
                        'id' => $leave->leaveType->id,
                        'name' => $leave->leaveType->name,
                        'quota' => $leave->leaveType->default_days,
                        'is_paid' => $leave->leaveType->is_paid,
                        'requires_attachment' => $leave->leaveType->requires_attachment ?? false,
                    ],
                    'start_date' => $leave->start_date->toDateString(),
                    'end_date' => $leave->end_date->toDateString(),
                    'total_days' => (float) $leave->total_days,
                    'is_half_day' => $leave->is_half_day,
                    'half_day_type' => $leave->half_day_type,
                    'reason' => $leave->reason,
                    'attachment' => $leave->attachment ? asset('storage/'.$leave->attachment) : null,
                    'status' => $leave->status,
                    'status_label' => $leave->status_label,
                    'approved_by' => $leave->approvedBy?->name,
                    'approved_at' => $leave->approved_at?->toDateTimeString(),
                    'rejection_reason' => $leave->rejection_reason,
                    'created_at' => $leave->created_at->toDateTimeString(),
                ];
            }),
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'last_page' => $leaves->lastPage(),
                'per_page' => $leaves->perPage(),
                'total' => $leaves->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/leaves',
        summary: 'Mengajukan cuti baru',
        description: 'Membuat pengajuan cuti baru dengan validasi saldo cuti dan overlap tanggal',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['leave_type_id', 'start_date', 'end_date', 'reason'],
                    properties: [
                        new OA\Property(property: 'leave_type_id', type: 'integer', example: 1, description: 'ID jenis cuti'),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-03-01', description: 'Tanggal mulai cuti (harus >= hari ini)'),
                        new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-03-05', description: 'Tanggal selesai cuti'),
                        new OA\Property(property: 'is_half_day', type: 'boolean', example: false, description: 'Apakah cuti setengah hari'),
                        new OA\Property(property: 'half_day_type', type: 'string', enum: ['morning', 'afternoon'], description: 'Jenis setengah hari (wajib jika is_half_day=true)'),
                        new OA\Property(property: 'reason', type: 'string', maxLength: 500, example: 'Liburan keluarga', description: 'Alasan pengajuan cuti'),
                        new OA\Property(property: 'attachment', type: 'string', format: 'binary', description: 'Lampiran (jpg, jpeg, png, pdf, max 2MB)'),
                        new OA\Property(property: 'emergency_contact', type: 'string', maxLength: 100, example: '081234567890', description: 'Kontak darurat selama cuti'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pengajuan cuti berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengajuan cuti berhasil dibuat.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'request_number', type: 'string', example: 'LV20260001'),
                                new OA\Property(property: 'leave_type', type: 'string', example: 'Cuti Tahunan'),
                                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-03-01'),
                                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-03-05'),
                                new OA\Property(property: 'total_days', type: 'number', format: 'float', example: 5.0),
                                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal atau saldo cuti tidak mencukupi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Sisa cuti tidak mencukupi.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'half_day_type' => 'nullable|string|in:morning,afternoon|required_if:is_half_day,true',
            'reason' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'emergency_contact' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $employee = $user->employee;
        $company = $user->company;

        // Calculate total days
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = $request->is_half_day ? 0.5 : ($startDate->diffInDays($endDate) + 1);

        // Check leave balance
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', $startDate->year)
            ->first();

        if ($balance) {
            $available = $balance->entitled_days - $balance->used_days - $balance->pending_days;
            if ($totalDays > $available) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sisa cuti tidak mencukupi.',
                ], 422);
            }
        }

        // Check overlapping leaves
        $overlapping = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal cuti bertabrakan dengan cuti yang sudah diajukan.',
            ], 422);
        }

        // Upload attachment if provided
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('leave-attachments/'.$company->id, 'public');
        }

        // Create leave request
        $leaveRequest = LeaveRequest::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'is_half_day' => $request->is_half_day ?? false,
            'half_day_type' => $request->half_day_type,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'emergency_contact' => $request->emergency_contact,
            'status' => 'pending',
        ]);

        // Update pending days in balance
        if ($balance) {
            $balance->increment('pending_days', $totalDays);
        }

        // Initialize approval workflow if configured
        app(ApprovalService::class)->initializeWorkflow(
            $leaveRequest,
            ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            $company->id
        );

        $leaveRequest->load('leaveType');

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dibuat.',
            'data' => [
                'id' => $leaveRequest->id,
                'request_number' => $leaveRequest->request_number,
                'leave_type' => $leaveRequest->leaveType->name,
                'start_date' => $leaveRequest->start_date->toDateString(),
                'end_date' => $leaveRequest->end_date->toDateString(),
                'total_days' => (float) $leaveRequest->total_days,
                'status' => $leaveRequest->status,
            ],
        ], 201);
    }

    #[OA\Get(
        path: '/leaves/{id}',
        summary: 'Mendapatkan detail pengajuan cuti',
        description: 'Mengembalikan detail lengkap pengajuan cuti termasuk informasi approval',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'ID pengajuan cuti'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail pengajuan cuti berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'request_number', type: 'string', example: 'LV20260001'),
                                new OA\Property(property: 'leave_type', type: 'string', example: 'Cuti Tahunan'),
                                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-03-01'),
                                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-03-05'),
                                new OA\Property(property: 'total_days', type: 'number', format: 'float', example: 5.0),
                                new OA\Property(property: 'is_half_day', type: 'boolean', example: false),
                                new OA\Property(property: 'reason', type: 'string', example: 'Liburan keluarga'),
                                new OA\Property(property: 'attachment', type: 'string', nullable: true, example: 'https://example.com/storage/leave-attachments/1/file.pdf'),
                                new OA\Property(property: 'status', type: 'string', example: 'approved'),
                                new OA\Property(property: 'status_label', type: 'string', example: 'Disetujui'),
                                new OA\Property(property: 'approved_by', type: 'string', nullable: true, example: 'John Doe'),
                                new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-12 10:30:00'),
                                new OA\Property(property: 'rejection_reason', type: 'string', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-12 08:00:00'),
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
    public function show(Request $request, LeaveRequest $leave): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if ($leave->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        $leave->load(['leaveType', 'approvedBy', 'rejectedBy']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $leave->id,
                'request_number' => $leave->request_number,
                'leave_type' => $leave->leaveType->name,
                'start_date' => $leave->start_date->toDateString(),
                'end_date' => $leave->end_date->toDateString(),
                'total_days' => (float) $leave->total_days,
                'is_half_day' => $leave->is_half_day,
                'reason' => $leave->reason,
                'attachment' => $leave->attachment ? asset('storage/'.$leave->attachment) : null,
                'status' => $leave->status,
                'status_label' => $leave->status_label,
                'approved_by' => $leave->approvedBy?->name,
                'approved_at' => $leave->approved_at?->toDateTimeString(),
                'rejection_reason' => $leave->rejection_reason,
                'created_at' => $leave->created_at->toDateTimeString(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/leaves/{id}/cancel',
        summary: 'Membatalkan pengajuan cuti',
        description: 'Membatalkan pengajuan cuti yang masih pending atau approved (dengan syarat belum dimulai)',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'ID pengajuan cuti'
            ),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string', maxLength: 500, example: 'Perubahan rencana mendadak', description: 'Alasan pembatalan (opsional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pengajuan cuti berhasil dibatalkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cuti berhasil dibatalkan.'),
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
                description: 'Cuti tidak dapat dibatalkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Cuti yang sudah berjalan tidak dapat dibatalkan.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function cancel(Request $request, LeaveRequest $leave): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if ($leave->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Check if can be cancelled
        if ($leave->isApproved() && $leave->start_date <= now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cuti yang sudah berjalan tidak dapat dibatalkan.',
            ], 422);
        }

        if (! $leave->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Cuti tidak dapat dibatalkan.',
            ], 422);
        }

        $leave->cancel($user->id, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Cuti berhasil dibatalkan.',
        ]);
    }

    #[OA\Get(
        path: '/leaves/balance',
        summary: 'Mendapatkan saldo cuti',
        description: 'Mengembalikan informasi saldo cuti karyawan untuk semua jenis cuti',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                in: 'query',
                schema: new OA\Schema(type: 'integer'),
                description: 'Tahun saldo cuti (default: tahun berjalan)'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Saldo cuti berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'leave_type_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'leave_type_name', type: 'string', example: 'Cuti Tahunan'),
                                    new OA\Property(property: 'year', type: 'integer', example: 2026),
                                    new OA\Property(property: 'entitled_days', type: 'number', format: 'float', example: 12.0, description: 'Hak cuti'),
                                    new OA\Property(property: 'used_days', type: 'number', format: 'float', example: 5.0, description: 'Cuti terpakai'),
                                    new OA\Property(property: 'pending_days', type: 'number', format: 'float', example: 2.0, description: 'Cuti dalam proses'),
                                    new OA\Property(property: 'remaining_days', type: 'number', format: 'float', example: 5.0, description: 'Sisa cuti'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function balance(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer',
        ]);

        $user = $request->user();
        $company = $user->company;
        $employee = $user->employee;
        $year = $request->year ?? $company->now()->year;

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $balances->map(function ($balance) {
                return [
                    'leave_type_id' => $balance->leave_type_id,
                    'leave_type_name' => $balance->leaveType->name,
                    'year' => $balance->year,
                    'entitled_days' => (float) $balance->entitled_days,
                    'used_days' => (float) $balance->used_days,
                    'pending_days' => (float) $balance->pending_days,
                    'remaining_days' => (float) ($balance->entitled_days - $balance->used_days - $balance->pending_days),
                ];
            }),
        ]);
    }

    #[OA\Get(
        path: '/leaves/types',
        summary: 'Mendapatkan daftar jenis cuti',
        description: 'Mengembalikan daftar semua jenis cuti yang aktif di perusahaan',
        tags: ['Leave'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar jenis cuti berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Cuti Tahunan'),
                                    new OA\Property(property: 'quota', type: 'integer', example: 12, description: 'Kuota default per tahun'),
                                    new OA\Property(property: 'is_paid', type: 'boolean', example: true, description: 'Apakah cuti dibayar'),
                                    new OA\Property(property: 'requires_attachment', type: 'boolean', example: false, description: 'Apakah memerlukan lampiran'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function types(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        $types = LeaveType::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'quota' => $type->default_days,
                    'is_paid' => $type->is_paid,
                    'requires_attachment' => $type->requires_attachment ?? false,
                ];
            }),
        ]);
    }
}
