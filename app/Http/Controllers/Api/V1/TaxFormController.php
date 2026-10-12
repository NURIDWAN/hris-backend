<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\TaxForm1721A1;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class TaxFormController extends Controller
{
    #[OA\Get(
        path: '/tax-forms',
        summary: 'Daftar Bukti Potong 1721-A1',
        description: 'Menampilkan daftar bukti potong pajak 1721-A1 untuk karyawan yang sedang login.',
        tags: ['Tax Form'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                in: 'query',
                description: 'Filter berdasarkan tahun pajak (contoh: 2025)',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2025)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mendapatkan daftar bukti potong',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'form_number', type: 'string', example: '1.1-12.345.678.9-012.000-2025'),
                                    new OA\Property(property: 'tax_year', type: 'integer', example: 2025),
                                    new OA\Property(property: 'employee_name', type: 'string', example: 'John Doe'),
                                    new OA\Property(property: 'gross_salary', type: 'number', format: 'float', example: 144000000),
                                    new OA\Property(property: 'formatted_gross_salary', type: 'string', example: 'Rp 144.000.000'),
                                    new OA\Property(property: 'total_pph21_withheld', type: 'number', format: 'float', example: 3600000),
                                    new OA\Property(property: 'formatted_pph21', type: 'string', example: 'Rp 3.600.000'),
                                    new OA\Property(property: 'generated_at', type: 'string', format: 'date', example: '2026-01-15'),
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

        $query = TaxForm1721A1::where('employee_id', $employee->id);

        if ($request->year) {
            $query->where('tax_year', $request->year);
        }

        $taxForms = $query->orderByDesc('tax_year')->get();

        return response()->json([
            'success' => true,
            'data' => $taxForms->map(function ($taxForm) {
                return [
                    'id' => $taxForm->id,
                    'form_number' => $taxForm->form_number,
                    'tax_year' => $taxForm->tax_year,
                    'employee_name' => $taxForm->employee_name,
                    'gross_salary' => (float) $taxForm->gross_salary,
                    'formatted_gross_salary' => 'Rp '.number_format($taxForm->gross_salary, 0, ',', '.'),
                    'total_pph21_withheld' => (float) $taxForm->total_pph21_withheld,
                    'formatted_pph21' => 'Rp '.number_format($taxForm->total_pph21_withheld, 0, ',', '.'),
                    'generated_at' => $taxForm->generated_at?->toDateString(),
                ];
            }),
        ]);
    }

    #[OA\Get(
        path: '/tax-forms/years',
        summary: 'Daftar Tahun Pajak Tersedia',
        description: 'Menampilkan daftar tahun pajak yang memiliki bukti potong untuk karyawan yang login.',
        tags: ['Tax Form'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mendapatkan daftar tahun',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'year', type: 'integer', example: 2025),
                                    new OA\Property(property: 'count', type: 'integer', example: 1),
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
    public function years(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $years = TaxForm1721A1::where('employee_id', $employee->id)
            ->selectRaw('tax_year as year, COUNT(*) as count')
            ->groupBy('tax_year')
            ->orderByDesc('tax_year')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $years->map(function ($item) {
                return [
                    'year' => $item->year,
                    'count' => (int) $item->count,
                ];
            }),
        ]);
    }

    #[OA\Get(
        path: '/tax-forms/{id}',
        summary: 'Detail Bukti Potong 1721-A1',
        description: 'Menampilkan detail lengkap bukti potong pajak 1721-A1 termasuk rincian penghasilan dan perhitungan PPh.',
        tags: ['Tax Form'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID bukti potong',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mendapatkan detail bukti potong',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Bukti potong tidak ditemukan',
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
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $taxForm = TaxForm1721A1::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $taxForm) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $taxForm->id,
                'form_number' => $taxForm->form_number,
                'tax_year' => $taxForm->tax_year,
                'company' => [
                    'npwp' => $taxForm->company_npwp,
                    'name' => $taxForm->company_name,
                    'address' => $taxForm->company_address,
                ],
                'employee' => [
                    'npwp' => $taxForm->employee_npwp,
                    'nik' => $taxForm->employee_nik,
                    'name' => $taxForm->employee_name,
                    'address' => $taxForm->employee_address,
                    'ptkp_status' => $taxForm->ptkp_status,
                ],
                'work_period' => $taxForm->work_period,
                'penghasilan_bruto' => [
                    'gaji_pokok' => (float) $taxForm->gaji_pokok,
                    'tunjangan_pph' => (float) $taxForm->tunjangan_pph,
                    'tunjangan_lainnya' => (float) $taxForm->tunjangan_lainnya,
                    'honorarium' => (float) $taxForm->honorarium,
                    'premi_asuransi' => (float) $taxForm->premi_asuransi,
                    'natura' => (float) $taxForm->natura,
                    'tantiem_bonus_thr' => (float) $taxForm->tantiem_bonus_thr,
                    'gross_salary' => (float) $taxForm->gross_salary,
                ],
                'pengurang' => [
                    'biaya_jabatan' => (float) $taxForm->biaya_jabatan,
                    'iuran_pensiun' => (float) $taxForm->iuran_pensiun,
                    'total_pengurang' => (float) $taxForm->total_pengurang,
                ],
                'perhitungan_pph' => [
                    'neto_salary' => (float) $taxForm->neto_salary,
                    'ptkp' => (float) $taxForm->ptkp,
                    'pkp' => (float) $taxForm->pkp,
                    'pph21_terutang' => (float) $taxForm->pph21_terutang,
                    'pph21_dipotong_sebelumnya' => (float) $taxForm->pph21_dipotong_sebelumnya,
                    'total_pph21_withheld' => (float) $taxForm->total_pph21_withheld,
                ],
                'generated_at' => $taxForm->generated_at?->toDateString(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/tax-forms/{id}/download',
        summary: 'Download Bukti Potong PDF',
        description: 'Mendapatkan URL untuk mengunduh bukti potong 1721-A1 dalam format PDF.',
        tags: ['Tax Form'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID bukti potong',
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
                                new OA\Property(property: 'download_url', type: 'string', example: 'https://app.gajipro.com/api/v1/tax-forms/1/pdf?token=xxx'),
                                new OA\Property(property: 'filename', type: 'string', example: 'Bukti_Potong_1721-A1_EMP20260001_2025.pdf'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Bukti potong tidak ditemukan',
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
    public function download(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $taxForm = TaxForm1721A1::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $taxForm) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Generate signed URL for PDF download
        $token = hash('sha256', $taxForm->id.'-'.$employee->id.'-'.config('app.key'));
        $downloadUrl = url("/api/v1/tax-forms/{$taxForm->id}/pdf?token={$token}");

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $downloadUrl,
                'filename' => sprintf(
                    'Bukti_Potong_1721-A1_%s_%d.pdf',
                    $employee->employee_id,
                    $taxForm->tax_year
                ),
            ],
        ]);
    }

    /**
     * Generate and return PDF for tax form
     */
    public function pdf(Request $request, int $id): Response|JsonResponse
    {
        $taxForm = TaxForm1721A1::with('employee')->find($id);

        if (! $taxForm) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Validate token
        $providedToken = $request->query('token');
        $employee = $taxForm->employee;
        $expectedToken = hash('sha256', $taxForm->id.'-'.$employee->id.'-'.config('app.key'));

        if ($providedToken !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa.',
            ], 403);
        }

        $pdf = Pdf::loadView('tax-forms.1721a1.pdf', [
            'taxForm' => $taxForm,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = sprintf(
            'Bukti_Potong_1721-A1_%s_%d.pdf',
            $employee->employee_id,
            $taxForm->tax_year
        );

        return $pdf->download($filename);
    }
}
