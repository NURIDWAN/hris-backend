<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReimbursementController extends Controller
{
    #[OA\Get(
        path: '/reimbursements',
        summary: 'Daftar pengajuan reimbursement karyawan',
        description: 'Mengambil daftar semua pengajuan reimbursement milik karyawan yang sedang login dengan pagination. Dapat difilter berdasarkan status dan rentang tanggal pengeluaran.',
        tags: ['Reimbursement'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter berdasarkan status reimbursement',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'paid'])
            ),
            new OA\Parameter(
                name: 'start_date',
                in: 'query',
                description: 'Tanggal awal pengeluaran (format: Y-m-d)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')
            ),
            new OA\Parameter(
                name: 'end_date',
                in: 'query',
                description: 'Tanggal akhir pengeluaran (format: Y-m-d)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-31')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Nomor halaman untuk pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar reimbursement berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'category', type: 'string', example: 'Transportasi'),
                                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150000),
                                    new OA\Property(property: 'formatted_amount', type: 'string', example: 'Rp 150.000'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Biaya taksi ke kantor cabang'),
                                    new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2026-02-10'),
                                    new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                    new OA\Property(property: 'status_label', type: 'string', example: 'Pending'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-12 10:30:00'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 42),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Token tidak valid atau kadaluarsa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - Parameter tidak valid',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The status field must be one of: pending, approved, rejected, paid.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'status',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The status field must be one of: pending, approved, rejected, paid.')
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,approved,rejected,paid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        $query = Reimbursement::with('category')
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        $reimbursements = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reimbursements->map(function ($reimbursement) {
                return [
                    'id' => $reimbursement->id,
                    'category' => $reimbursement->category?->name,
                    'amount' => (float) $reimbursement->amount,
                    'formatted_amount' => $reimbursement->formatted_amount,
                    'description' => $reimbursement->description,
                    'expense_date' => $reimbursement->expense_date->toDateString(),
                    'status' => $reimbursement->status,
                    'status_label' => $reimbursement->status_label,
                    'created_at' => $reimbursement->created_at->toDateTimeString(),
                ];
            }),
            'meta' => [
                'current_page' => $reimbursements->currentPage(),
                'last_page' => $reimbursements->lastPage(),
                'per_page' => $reimbursements->perPage(),
                'total' => $reimbursements->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/reimbursements',
        summary: 'Buat pengajuan reimbursement baru',
        description: 'Membuat pengajuan reimbursement baru untuk karyawan yang sedang login. Dapat menyertakan bukti struk/kwitansi dalam bentuk file gambar atau PDF.',
        tags: ['Reimbursement'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['category_id', 'amount', 'description', 'expense_date'],
                    properties: [
                        new OA\Property(
                            property: 'category_id',
                            description: 'ID kategori reimbursement',
                            type: 'integer',
                            example: 1
                        ),
                        new OA\Property(
                            property: 'amount',
                            description: 'Jumlah reimbursement dalam Rupiah (tanpa format)',
                            type: 'number',
                            format: 'float',
                            example: 150000
                        ),
                        new OA\Property(
                            property: 'description',
                            description: 'Deskripsi pengeluaran (maksimal 500 karakter)',
                            type: 'string',
                            example: 'Biaya taksi untuk kunjungan ke kantor cabang Jakarta'
                        ),
                        new OA\Property(
                            property: 'expense_date',
                            description: 'Tanggal pengeluaran (format: Y-m-d)',
                            type: 'string',
                            format: 'date',
                            example: '2026-02-10'
                        ),
                        new OA\Property(
                            property: 'receipt',
                            description: 'File bukti struk/kwitansi (opsional). Format: JPG, PNG, atau PDF. Maksimal 2MB.',
                            type: 'string',
                            format: 'binary',
                            nullable: true
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Reimbursement berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengajuan reimbursement berhasil dibuat.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'category', type: 'string', example: 'Transportasi'),
                                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150000),
                                new OA\Property(property: 'formatted_amount', type: 'string', example: 'Rp 150.000'),
                                new OA\Property(property: 'description', type: 'string', example: 'Biaya taksi ke kantor cabang'),
                                new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2026-02-10'),
                                new OA\Property(property: 'receipt_url', type: 'string', example: 'https://example.com/storage/reimbursement-receipts/1/receipt.jpg', nullable: true),
                                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Token tidak valid atau kadaluarsa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error atau jumlah melebihi batas kategori',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Jumlah melebihi batas maksimal kategori (Rp 500.000).'),
                    ]
                )
            ),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:reimbursement_categories,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
            'expense_date' => 'required|date',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $user = $request->user();
        $employee = $user->employee;
        $company = $user->company;

        // Validate category belongs to company
        $category = ReimbursementCategory::where('id', $request->category_id)
            ->where('company_id', $company->id)
            ->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan.',
            ], 422);
        }

        // Check max amount
        if ($category->max_amount && $request->amount > $category->max_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah melebihi batas maksimal kategori (Rp '.number_format($category->max_amount, 0, ',', '.').').',
            ], 422);
        }

        // Upload receipt if provided
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')
                ->store('reimbursement-receipts/'.$company->id, 'public');
        }

        // Create reimbursement
        $reimbursement = Reimbursement::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'expense_date' => $request->expense_date,
            'receipt_path' => $receiptPath,
            'status' => Reimbursement::STATUS_PENDING,
        ]);

        $reimbursement->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan reimbursement berhasil dibuat.',
            'data' => [
                'id' => $reimbursement->id,
                'category' => $reimbursement->category->name,
                'amount' => (float) $reimbursement->amount,
                'formatted_amount' => $reimbursement->formatted_amount,
                'description' => $reimbursement->description,
                'expense_date' => $reimbursement->expense_date->toDateString(),
                'receipt_url' => $receiptPath ? asset('storage/'.$receiptPath) : null,
                'status' => $reimbursement->status,
            ],
        ], 201);
    }

    #[OA\Get(
        path: '/reimbursements/{id}',
        summary: 'Detail pengajuan reimbursement',
        description: 'Mengambil informasi detail dari satu pengajuan reimbursement berdasarkan ID, termasuk informasi kategori, approver, dan status pembayaran.',
        tags: ['Reimbursement'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID reimbursement',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail reimbursement berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'category',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Transportasi'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150000),
                                new OA\Property(property: 'formatted_amount', type: 'string', example: 'Rp 150.000'),
                                new OA\Property(property: 'description', type: 'string', example: 'Biaya taksi ke kantor cabang'),
                                new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2026-02-10'),
                                new OA\Property(property: 'receipt_url', type: 'string', example: 'https://example.com/storage/reimbursement-receipts/1/receipt.jpg', nullable: true),
                                new OA\Property(property: 'status', type: 'string', example: 'approved'),
                                new OA\Property(property: 'status_label', type: 'string', example: 'Disetujui'),
                                new OA\Property(property: 'approved_by', type: 'string', example: 'John Doe', nullable: true),
                                new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', example: '2026-02-11 14:30:00', nullable: true),
                                new OA\Property(property: 'rejection_reason', type: 'string', example: null, nullable: true),
                                new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                new OA\Property(property: 'payment_method', type: 'string', example: null, nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-10 09:15:00'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Token tidak valid atau kadaluarsa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Reimbursement tidak ditemukan atau bukan milik karyawan ini',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data tidak ditemukan.'),
                    ]
                )
            ),
        ]
    )]
    public function show(Request $request, Reimbursement $reimbursement): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if ($reimbursement->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        $reimbursement->load(['category', 'approver']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $reimbursement->id,
                'category' => [
                    'id' => $reimbursement->category?->id,
                    'name' => $reimbursement->category?->name,
                ],
                'amount' => (float) $reimbursement->amount,
                'formatted_amount' => $reimbursement->formatted_amount,
                'description' => $reimbursement->description,
                'expense_date' => $reimbursement->expense_date->toDateString(),
                'receipt_url' => $reimbursement->receipt_path ? asset('storage/'.$reimbursement->receipt_path) : null,
                'status' => $reimbursement->status,
                'status_label' => $reimbursement->status_label,
                'approved_by' => $reimbursement->approver?->name,
                'approved_at' => $reimbursement->approved_at?->toDateTimeString(),
                'rejection_reason' => $reimbursement->rejection_reason,
                'paid_at' => $reimbursement->paid_at?->toDateTimeString(),
                'payment_method' => $reimbursement->payment_method,
                'created_at' => $reimbursement->created_at->toDateTimeString(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/reimbursements/categories',
        summary: 'Daftar kategori reimbursement aktif',
        description: 'Mengambil daftar semua kategori reimbursement yang aktif di perusahaan karyawan. Digunakan untuk memilih kategori saat membuat pengajuan reimbursement baru.',
        tags: ['Reimbursement'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar kategori berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Transportasi'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Biaya transportasi dinas', nullable: true),
                                    new OA\Property(property: 'max_amount', type: 'number', format: 'float', example: 500000, nullable: true),
                                    new OA\Property(property: 'formatted_max_amount', type: 'string', example: 'Rp 500.000', nullable: true),
                                    new OA\Property(property: 'requires_receipt', type: 'boolean', example: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Token tidak valid atau kadaluarsa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function categories(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        $categories = ReimbursementCategory::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'max_amount' => (float) $category->max_amount,
                    'formatted_max_amount' => $category->max_amount
                        ? 'Rp '.number_format($category->max_amount, 0, ',', '.')
                        : null,
                    'requires_receipt' => $category->requires_receipt ?? false,
                ];
            }),
        ]);
    }

    #[OA\Get(
        path: '/reimbursements/summary',
        summary: 'Ringkasan reimbursement karyawan per bulan',
        description: 'Mengambil ringkasan statistik reimbursement karyawan untuk bulan dan tahun tertentu, termasuk jumlah pengajuan per status dan total nominal.',
        tags: ['Reimbursement'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'month',
                in: 'query',
                description: 'Bulan (1-12)',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                description: 'Tahun (format: YYYY)',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2026)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ringkasan reimbursement berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'total_requests', type: 'integer', example: 5, description: 'Total pengajuan reimbursement'),
                                new OA\Property(property: 'pending_requests', type: 'integer', example: 2, description: 'Jumlah pengajuan pending'),
                                new OA\Property(property: 'approved_requests', type: 'integer', example: 2, description: 'Jumlah pengajuan disetujui'),
                                new OA\Property(property: 'paid_requests', type: 'integer', example: 1, description: 'Jumlah pengajuan sudah dibayar'),
                                new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 750000, description: 'Total nominal semua pengajuan'),
                                new OA\Property(property: 'approved_amount', type: 'number', format: 'float', example: 450000, description: 'Total nominal yang disetujui'),
                                new OA\Property(property: 'paid_amount', type: 'number', format: 'float', example: 150000, description: 'Total nominal yang sudah dibayar'),
                                new OA\Property(property: 'pending_amount', type: 'number', format: 'float', example: 300000, description: 'Total nominal yang masih pending'),
                                new OA\Property(property: 'formatted_total_amount', type: 'string', example: 'Rp 750.000', description: 'Total nominal terformat'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Token tidak valid atau kadaluarsa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error - Parameter tidak valid',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The month field must be between 1 and 12.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'month',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The month field must be between 1 and 12.')
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
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

        $reimbursements = Reimbursement::where('employee_id', $employee->id)
            ->whereYear('expense_date', $request->year)
            ->whereMonth('expense_date', $request->month)
            ->get();

        $pending = $reimbursements->where('status', Reimbursement::STATUS_PENDING);
        $approved = $reimbursements->where('status', Reimbursement::STATUS_APPROVED);
        $paid = $reimbursements->where('status', Reimbursement::STATUS_PAID);

        return response()->json([
            'success' => true,
            'data' => [
                'total_requests' => $reimbursements->count(),
                'pending_requests' => $pending->count(),
                'approved_requests' => $approved->count(),
                'paid_requests' => $paid->count(),
                'total_amount' => (float) $reimbursements->sum('amount'),
                'approved_amount' => (float) $approved->sum('amount'),
                'paid_amount' => (float) $paid->sum('amount'),
                'pending_amount' => (float) $pending->sum('amount'),
                'formatted_total_amount' => 'Rp '.number_format($reimbursements->sum('amount'), 0, ',', '.'),
            ],
        ]);
    }
}
