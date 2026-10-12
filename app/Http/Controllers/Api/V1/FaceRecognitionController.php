<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FaceRecognitionController extends Controller
{
    public function __construct(
        protected FaceRecognitionService $faceRecognitionService
    ) {}

    #[OA\Get(
        path: '/face-recognition/status',
        summary: 'Status Pendaftaran Wajah',
        description: 'Mengambil status pendaftaran wajah karyawan untuk keperluan absensi. Mengembalikan informasi apakah wajah sudah terdaftar beserta pengaturan face recognition perusahaan.',
        tags: ['FaceRecognition'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status pendaftaran wajah berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'enrolled', type: 'boolean', example: true, description: 'Apakah wajah sudah terdaftar'),
                                new OA\Property(property: 'enrolled_at', type: 'string', format: 'date-time', example: '2026-01-15T10:30:00+07:00', nullable: true, description: 'Waktu pendaftaran wajah'),
                                new OA\Property(property: 'quality_score', type: 'number', format: 'float', example: 0.95, nullable: true, description: 'Skor kualitas embedding wajah (0-1)'),
                                new OA\Property(
                                    property: 'company_settings',
                                    properties: [
                                        new OA\Property(property: 'face_recognition_enabled', type: 'boolean', example: true, description: 'Apakah face recognition diaktifkan di perusahaan'),
                                        new OA\Property(property: 'liveness_required', type: 'boolean', example: true, description: 'Apakah liveness detection diperlukan'),
                                        new OA\Property(property: 'match_threshold', type: 'number', format: 'float', example: 0.6, description: 'Threshold kecocokan wajah (0-1)'),
                                    ],
                                    type: 'object'
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
        ]
    )]
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found for this user.',
            ], 404);
        }

        $company = $user->company;
        $status = $this->faceRecognitionService->getEnrollmentStatus($employee);

        return response()->json([
            'data' => [
                'enrolled' => $status['enrolled'],
                'enrolled_at' => $status['enrolled_at'],
                'quality_score' => $status['quality_score'],
                'company_settings' => [
                    'face_recognition_enabled' => $company->enable_face_recognition ?? false,
                    'liveness_required' => $company->require_liveness_detection ?? true,
                    'match_threshold' => $company->face_match_threshold ?? 0.6,
                ],
            ],
        ]);
    }

    #[OA\Post(
        path: '/face-recognition/enroll',
        summary: 'Daftarkan Wajah',
        description: 'Mendaftarkan wajah karyawan untuk verifikasi saat absensi. Mengirimkan embedding data dari face-api.js dan opsional foto wajah.',
        tags: ['FaceRecognition'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['embedding_data'],
                    properties: [
                        new OA\Property(
                            property: 'embedding_data',
                            type: 'object',
                            required: ['version', 'model', 'descriptors'],
                            properties: [
                                new OA\Property(property: 'version', type: 'string', example: '1.0', description: 'Versi format embedding'),
                                new OA\Property(property: 'model', type: 'string', example: 'face-api.js', description: 'Model yang digunakan untuk ekstraksi embedding'),
                                new OA\Property(
                                    property: 'descriptors',
                                    type: 'array',
                                    description: 'Array 128 nilai float descriptor wajah',
                                    items: new OA\Items(type: 'number', format: 'float'),
                                    minItems: 128,
                                    maxItems: 128
                                ),
                            ],
                            description: 'Data embedding wajah dari face-api.js'
                        ),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'Foto wajah (opsional, max 5MB)', nullable: true),
                        new OA\Property(property: 'quality_score', type: 'number', format: 'float', example: 0.95, description: 'Skor kualitas gambar (0-1)', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Wajah berhasil didaftarkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Face enrolled successfully.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'enrolled', type: 'boolean', example: true),
                                new OA\Property(property: 'enrolled_at', type: 'string', format: 'date-time', example: '2026-02-15T10:30:00+07:00'),
                                new OA\Property(property: 'quality_score', type: 'number', format: 'float', example: 0.95),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Face recognition tidak diaktifkan di perusahaan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Face recognition is not enabled for this company.'),
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
    public function enroll(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found for this user.',
            ], 404);
        }

        $company = $user->company;

        if (! $company->enable_face_recognition) {
            return response()->json([
                'message' => 'Face recognition is not enabled for this company.',
            ], 403);
        }

        // Support both 'embedding' (new format) and 'descriptors' (legacy)
        $hasEmbedding = $request->has('embedding_data.embedding');
        $embeddingField = $hasEmbedding ? 'embedding' : 'descriptors';

        $validated = $request->validate([
            'embedding_data' => ['required', 'array'],
            'embedding_data.version' => ['required', 'string'],
            'embedding_data.model' => ['required', 'string'],
            // Support both 128 (face-api.js) and 192 (MobileFaceNet TFLite) dimensions
            "embedding_data.{$embeddingField}" => ['required', 'array', 'min:128', 'max:192'],
            "embedding_data.{$embeddingField}.*" => ['required', 'numeric'],
            'photo' => ['nullable', 'image', 'max:5120'], // 5MB max
            'quality_score' => ['nullable', 'numeric', 'between:0,1'],
        ], [
            'embedding_data.required' => 'Face embedding data is required.',
            "embedding_data.{$embeddingField}.required" => 'Face embedding array is required.',
            "embedding_data.{$embeddingField}.min" => 'Face embedding must have at least 128 values.',
            "embedding_data.{$embeddingField}.max" => 'Face embedding must have at most 192 values.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('face-enrollments', 'public');
        }

        $embedding = $this->faceRecognitionService->enrollFace(
            $employee,
            $validated['embedding_data'],
            $photoPath ?? 'no-photo',
            $user,
            $validated['quality_score'] ?? null
        );

        return response()->json([
            'message' => 'Face enrolled successfully.',
            'data' => [
                'enrolled' => true,
                'enrolled_at' => $embedding->enrolled_at->toIso8601String(),
                'quality_score' => $embedding->quality_score,
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/face-recognition/verify',
        summary: 'Verifikasi Wajah',
        description: 'Memverifikasi wajah karyawan dengan embedding yang terdaftar. Digunakan untuk validasi saat clock in/out.',
        tags: ['FaceRecognition'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['descriptors'],
                    properties: [
                        new OA\Property(
                            property: 'descriptors',
                            type: 'array',
                            description: 'Array 128 nilai float descriptor wajah dari face-api.js',
                            items: new OA\Items(type: 'number', format: 'float'),
                            minItems: 128,
                            maxItems: 128
                        ),
                        new OA\Property(property: 'verification_type', type: 'string', enum: ['clock_in', 'clock_out'], example: 'clock_in', description: 'Tipe verifikasi', nullable: true),
                        new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'Foto wajah untuk log (opsional, max 5MB)', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Hasil verifikasi wajah',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'matched', type: 'boolean', example: true, description: 'Apakah wajah cocok'),
                                new OA\Property(property: 'confidence', type: 'number', format: 'float', example: 0.85, description: 'Tingkat kecocokan (0-1)'),
                                new OA\Property(property: 'error', type: 'string', example: null, nullable: true, description: 'Pesan error jika gagal'),
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
    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found for this user.',
            ], 404);
        }

        $company = $user->company;

        $validated = $request->validate([
            // Support both 128 (face-api.js) and 192 (MobileFaceNet TFLite) dimensions
            'descriptors' => ['required', 'array', 'min:128', 'max:192'],
            'descriptors.*' => ['required', 'numeric'],
            'verification_type' => ['nullable', 'in:clock_in,clock_out'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ], [
            'descriptors.required' => 'Face descriptors are required.',
            'descriptors.min' => 'Face descriptors must have at least 128 values.',
            'descriptors.max' => 'Face descriptors must have at most 192 values.',
            'verification_type.in' => 'Verification type must be clock_in or clock_out.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('face-verifications', 'public');
        }

        $threshold = $company->face_match_threshold ?? 0.6;
        $verificationType = $validated['verification_type'] ?? 'clock_in';

        $result = $this->faceRecognitionService->verifyFace(
            $employee,
            $validated['descriptors'],
            $threshold,
            $verificationType,
            $photoPath
        );

        return response()->json([
            'data' => [
                'matched' => $result['matched'],
                'confidence' => $result['confidence'],
                'error' => $result['error'] ?? null,
            ],
        ]);
    }

    #[OA\Delete(
        path: '/face-recognition/enrollment',
        summary: 'Hapus Pendaftaran Wajah',
        description: 'Menghapus data pendaftaran wajah karyawan. Setelah dihapus, karyawan perlu mendaftarkan wajah lagi untuk menggunakan fitur face recognition.',
        tags: ['FaceRecognition'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pendaftaran wajah berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Face enrollment removed successfully.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Pendaftaran wajah tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No face enrollment found.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function removeEnrollment(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found for this user.',
            ], 404);
        }

        $removed = $this->faceRecognitionService->removeEnrollment($employee);

        if (! $removed) {
            return response()->json([
                'message' => 'No face enrollment found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Face enrollment removed successfully.',
        ]);
    }
}
