<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class PayslipController extends Controller
{
    #[OA\Get(
        path: '/payslips',
        summary: 'Daftar Slip Gaji',
        description: 'Menampilkan daftar slip gaji karyawan yang sudah dibayar. Bisa difilter berdasarkan tahun.',
        tags: ['Payslip'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                in: 'query',
                description: 'Filter berdasarkan tahun (contoh: 2026)',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2026)
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
                description: 'Berhasil mendapatkan daftar slip gaji',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'payroll_id', type: 'integer', example: 5),
                                    new OA\Property(property: 'period', type: 'string', example: 'Januari 2026'),
                                    new OA\Property(property: 'period_month', type: 'integer', example: 1),
                                    new OA\Property(property: 'period_year', type: 'integer', example: 2026),
                                    new OA\Property(property: 'net_salary', type: 'number', format: 'float', example: 8500000),
                                    new OA\Property(property: 'formatted_net_salary', type: 'string', example: 'Rp 8.500.000'),
                                    new OA\Property(property: 'payment_date', type: 'string', format: 'date', example: '2026-02-01'),
                                    new OA\Property(property: 'status', type: 'string', example: 'paid'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Tidak terautentikasi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        // Optimized: Use join instead of whereHas for better performance
        $query = PayrollItem::select('payroll_items.*')
            ->join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->where('payroll_items.employee_id', $employee->id)
            ->where('payrolls.status', 'paid');

        if ($request->year) {
            $query->where('payrolls.period_year', $request->year);
        }

        $query->orderByDesc('payrolls.period_year')
            ->orderByDesc('payrolls.period_month');

        $payslips = $query->with('payroll')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payslips->map(function ($item) {
                $payroll = $item->payroll;

                return [
                    'id' => $item->id,
                    'payroll_id' => $payroll->id,
                    'period' => $payroll->period_label,
                    'period_month' => $payroll->period_month,
                    'period_year' => $payroll->period_year,
                    'net_salary' => (float) $item->net_salary,
                    'formatted_net_salary' => 'Rp '.number_format($item->net_salary, 0, ',', '.'),
                    'payment_date' => $payroll->payment_date?->toDateString(),
                    'status' => $item->status,
                ];
            }),
        ]);
    }

    #[OA\Get(
        path: '/payslips/{id}',
        summary: 'Detail Slip Gaji',
        description: 'Menampilkan detail slip gaji termasuk rincian penghasilan (earnings) dan potongan (deductions).',
        tags: ['Payslip'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID dari slip gaji (payroll_item_id)',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mendapatkan detail slip gaji',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'payroll_id', type: 'integer', example: 5),
                                new OA\Property(property: 'period', type: 'string', example: 'Januari 2026'),
                                new OA\Property(property: 'period_month', type: 'integer', example: 1),
                                new OA\Property(property: 'period_year', type: 'integer', example: 2026),
                                new OA\Property(
                                    property: 'employee',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'employee_id', type: 'string', example: 'EMP20260001'),
                                        new OA\Property(property: 'full_name', type: 'string', example: 'John Doe'),
                                        new OA\Property(property: 'department', type: 'string', example: 'IT'),
                                        new OA\Property(property: 'position', type: 'string', example: 'Software Engineer'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'attendance',
                                    description: 'Ringkasan kehadiran periode ini',
                                    properties: [
                                        new OA\Property(property: 'working_days', type: 'integer', example: 22, description: 'Jumlah hari kerja'),
                                        new OA\Property(property: 'present_days', type: 'integer', example: 20, description: 'Jumlah hari hadir'),
                                        new OA\Property(property: 'absent_days', type: 'integer', example: 2, description: 'Jumlah hari tidak hadir'),
                                        new OA\Property(property: 'late_days', type: 'integer', example: 1, description: 'Jumlah hari terlambat'),
                                        new OA\Property(property: 'leave_days', type: 'integer', example: 0, description: 'Jumlah hari cuti'),
                                        new OA\Property(property: 'overtime_hours', type: 'integer', example: 5, description: 'Jumlah jam lembur'),
                                        new OA\Property(property: 'attendance_percentage', type: 'number', format: 'float', example: 90.9, description: 'Persentase kehadiran'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'base_salary', type: 'number', format: 'float', example: 8000000),
                                new OA\Property(
                                    property: 'earnings',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'name', type: 'string', example: 'Tunjangan Transport'),
                                            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500000),
                                            new OA\Property(property: 'formatted_amount', type: 'string', example: 'Rp 500.000'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'deductions',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'name', type: 'string', example: 'BPJS Kesehatan'),
                                            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 80000),
                                            new OA\Property(property: 'formatted_amount', type: 'string', example: 'Rp 80.000'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(property: 'total_earnings', type: 'number', format: 'float', example: 8500000),
                                new OA\Property(property: 'total_deductions', type: 'number', format: 'float', example: 200000),
                                new OA\Property(property: 'net_salary', type: 'number', format: 'float', example: 8300000),
                                new OA\Property(property: 'formatted_base_salary', type: 'string', example: 'Rp 8.000.000'),
                                new OA\Property(property: 'formatted_total_earnings', type: 'string', example: 'Rp 8.500.000'),
                                new OA\Property(property: 'formatted_total_deductions', type: 'string', example: 'Rp 200.000'),
                                new OA\Property(property: 'formatted_net_salary', type: 'string', example: 'Rp 8.300.000'),
                                new OA\Property(property: 'payment_date', type: 'string', format: 'date', example: '2026-02-01'),
                                new OA\Property(property: 'payment_method', type: 'string', example: 'Transfer Bank'),
                                new OA\Property(property: 'status', type: 'string', example: 'paid'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Slip gaji tidak ditemukan atau tidak dapat diakses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data tidak ditemukan.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Tidak terautentikasi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function show(Request $request, PayrollItem $payslip): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if ($payslip->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        $payslip->load(['payroll', 'employee.department', 'employee.position', 'details']);

        $payroll = $payslip->payroll;

        if ($payroll->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Group details by type (earning/deduction)
        $earnings = [];
        $deductions = [];

        foreach ($payslip->details as $detail) {
            $item = [
                'name' => $detail->component_name,
                'amount' => (float) $detail->amount,
                'formatted_amount' => 'Rp '.number_format($detail->amount, 0, ',', '.'),
            ];

            if ($detail->type === 'earning') {
                $earnings[] = $item;
            } else {
                $deductions[] = $item;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payslip->id,
                'payroll_id' => $payroll->id,
                'period' => $payroll->period_label,
                'period_month' => $payroll->period_month,
                'period_year' => $payroll->period_year,
                'employee' => [
                    'id' => $payslip->employee->id,
                    'employee_id' => $payslip->employee->employee_id,
                    'full_name' => $payslip->employee->full_name,
                    'department' => $payslip->employee->department?->name,
                    'position' => $payslip->employee->position?->name,
                ],
                'attendance' => [
                    'working_days' => (int) $payslip->working_days,
                    'present_days' => (int) $payslip->present_days,
                    'absent_days' => (int) $payslip->absent_days,
                    'late_days' => (int) ($payslip->late_days ?? 0),
                    'leave_days' => (int) ($payslip->leave_days ?? 0),
                    'overtime_hours' => (int) ($payslip->overtime_hours ?? 0),
                    'attendance_percentage' => $payslip->working_days > 0
                        ? round(($payslip->present_days / $payslip->working_days) * 100, 1)
                        : 0,
                ],
                'base_salary' => (float) $payslip->basic_salary,
                'earnings' => $earnings,
                'deductions' => $deductions,
                'total_earnings' => (float) $payslip->total_earnings,
                'total_deductions' => (float) $payslip->total_deductions,
                'net_salary' => (float) $payslip->net_salary,
                'formatted_base_salary' => 'Rp '.number_format($payslip->basic_salary, 0, ',', '.'),
                'formatted_total_earnings' => 'Rp '.number_format($payslip->total_earnings, 0, ',', '.'),
                'formatted_total_deductions' => 'Rp '.number_format($payslip->total_deductions, 0, ',', '.'),
                'formatted_net_salary' => 'Rp '.number_format($payslip->net_salary, 0, ',', '.'),
                'payment_date' => $payroll->payment_date?->toDateString(),
                'payment_method' => $payslip->payment_method ?? 'Transfer Bank',
                'status' => $payslip->status,
            ],
        ]);
    }

    #[OA\Get(
        path: '/payslips/{id}/download',
        summary: 'Download Slip Gaji PDF',
        description: 'Mendapatkan URL untuk mengunduh slip gaji dalam format PDF.',
        tags: ['Payslip'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID dari slip gaji (payroll_item_id)',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mendapatkan URL download',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'download_url', type: 'string', example: 'https://app.gajipro.com/api/v1/payslips/1/pdf?token=xxx'),
                                new OA\Property(property: 'filename', type: 'string', example: 'Slip_Gaji_EMP20260001_1_2026.pdf'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Slip gaji tidak ditemukan atau tidak dapat diakses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data tidak ditemukan.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Tidak terautentikasi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function download(Request $request, PayrollItem $payslip): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if ($payslip->employee_id !== $employee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        $payslip->load('payroll');
        $payroll = $payslip->payroll;

        // Generate signed URL for PDF download (valid for 5 minutes)
        $token = hash('sha256', $payslip->id.'-'.$employee->id.'-'.config('app.key'));
        $downloadUrl = url("/api/v1/payslips/{$payslip->id}/pdf?token={$token}");

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $downloadUrl,
                'filename' => sprintf('Slip_Gaji_%s_%s_%d.pdf', $employee->employee_id, $payroll->period_month, $payroll->period_year),
            ],
        ]);
    }

    /**
     * Generate and return PDF for payslip
     */
    public function pdf(Request $request, PayrollItem $payslip): Response|JsonResponse
    {
        // Validate token
        $providedToken = $request->query('token');
        $employee = $payslip->employee;
        $expectedToken = hash('sha256', $payslip->id.'-'.$employee->id.'-'.config('app.key'));

        if ($providedToken !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa.',
            ], 403);
        }

        $payslip->load(['payroll', 'employee.department', 'employee.position', 'employee.user.company', 'details']);

        $payroll = $payslip->payroll;
        $company = $employee->user->company;

        // Group details by type
        $earnings = [];
        $deductions = [];

        foreach ($payslip->details as $detail) {
            $item = [
                'name' => $detail->component_name,
                'amount' => (float) $detail->amount,
            ];

            if ($detail->type === 'earning') {
                $earnings[] = $item;
            } else {
                $deductions[] = $item;
            }
        }

        $pdf = Pdf::loadView('pdf.payslip', [
            'payslip' => $payslip,
            'payroll' => $payroll,
            'employee' => $employee,
            'company' => $company,
            'earnings' => $earnings,
            'deductions' => $deductions,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = sprintf('Slip_Gaji_%s_%s_%d.pdf',
            $employee->employee_id,
            $payroll->period_month,
            $payroll->period_year
        );

        return $pdf->download($filename);
    }

    #[OA\Get(
        path: '/payslips/summary',
        summary: 'Ringkasan Penghasilan Tahunan',
        description: 'Menampilkan ringkasan penghasilan karyawan per tahun, termasuk total earnings, deductions, net salary, dan breakdown per bulan.',
        tags: ['Payslip'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                in: 'query',
                description: 'Tahun untuk ringkasan (contoh: 2026)',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2026)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mendapatkan ringkasan penghasilan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'year', type: 'integer', example: 2026),
                                new OA\Property(property: 'total_months', type: 'integer', example: 12),
                                new OA\Property(property: 'total_earnings', type: 'number', format: 'float', example: 102000000),
                                new OA\Property(property: 'total_deductions', type: 'number', format: 'float', example: 2400000),
                                new OA\Property(property: 'total_net_salary', type: 'number', format: 'float', example: 99600000),
                                new OA\Property(property: 'average_net_salary', type: 'number', format: 'float', example: 8300000),
                                new OA\Property(property: 'formatted_total_earnings', type: 'string', example: 'Rp 102.000.000'),
                                new OA\Property(property: 'formatted_total_deductions', type: 'string', example: 'Rp 2.400.000'),
                                new OA\Property(property: 'formatted_total_net_salary', type: 'string', example: 'Rp 99.600.000'),
                                new OA\Property(property: 'formatted_average_net_salary', type: 'string', example: 'Rp 8.300.000'),
                                new OA\Property(
                                    property: 'monthly_breakdown',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'month', type: 'integer', example: 1),
                                            new OA\Property(property: 'month_name', type: 'string', example: 'Januari'),
                                            new OA\Property(property: 'net_salary', type: 'number', format: 'float', example: 8300000),
                                            new OA\Property(property: 'formatted_net_salary', type: 'string', example: 'Rp 8.300.000'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The year field is required.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'year',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The year field is required.')
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Tidak terautentikasi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
        ]);

        $user = $request->user();
        $employee = $user->employee;
        $year = (int) $request->year;

        // Optimized: Use database aggregate instead of loading all records
        $totals = PayrollItem::join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->where('payroll_items.employee_id', $employee->id)
            ->where('payrolls.status', 'paid')
            ->where('payrolls.period_year', $year)
            ->selectRaw('
                COUNT(*) as months_count,
                COALESCE(SUM(payroll_items.total_earnings), 0) as total_earnings,
                COALESCE(SUM(payroll_items.total_deductions), 0) as total_deductions,
                COALESCE(SUM(payroll_items.net_salary), 0) as total_net_salary
            ')
            ->first();

        $monthsCount = (int) ($totals->months_count ?? 0);
        $totalEarnings = (float) ($totals->total_earnings ?? 0);
        $totalDeductions = (float) ($totals->total_deductions ?? 0);
        $totalNetSalary = (float) ($totals->total_net_salary ?? 0);

        // Get monthly breakdown with optimized query
        $monthlyData = PayrollItem::join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->where('payroll_items.employee_id', $employee->id)
            ->where('payrolls.status', 'paid')
            ->where('payrolls.period_year', $year)
            ->select('payrolls.period_month', 'payroll_items.net_salary')
            ->orderBy('payrolls.period_month')
            ->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthlyBreakdown = $monthlyData->map(function ($item) use ($monthNames) {
            $month = $item->period_month;

            return [
                'month' => $month,
                'month_name' => $monthNames[$month],
                'net_salary' => (float) $item->net_salary,
                'formatted_net_salary' => 'Rp '.number_format($item->net_salary, 0, ',', '.'),
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'total_months' => $monthsCount,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'total_net_salary' => $totalNetSalary,
                'average_net_salary' => $monthsCount > 0 ? round($totalNetSalary / $monthsCount, 2) : 0,
                'formatted_total_earnings' => 'Rp '.number_format($totalEarnings, 0, ',', '.'),
                'formatted_total_deductions' => 'Rp '.number_format($totalDeductions, 0, ',', '.'),
                'formatted_total_net_salary' => 'Rp '.number_format($totalNetSalary, 0, ',', '.'),
                'formatted_average_net_salary' => 'Rp '.number_format($monthsCount > 0 ? $totalNetSalary / $monthsCount : 0, 0, ',', '.'),
                'monthly_breakdown' => $monthlyBreakdown,
            ],
        ]);
    }
}
