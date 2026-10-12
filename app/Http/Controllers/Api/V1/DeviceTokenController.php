<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DeviceTokenController extends Controller
{
    #[OA\Get(
        path: '/device-tokens',
        summary: 'Daftar Device Token',
        description: 'Menampilkan daftar semua device token yang terdaftar untuk user yang sedang login. Diurutkan berdasarkan waktu terakhir digunakan.',
        tags: ['DeviceToken'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar device token berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'platform', type: 'string', enum: ['android', 'ios', 'web'], example: 'android'),
                                    new OA\Property(property: 'device_name', type: 'string', example: 'Samsung Galaxy S24', nullable: true),
                                    new OA\Property(property: 'device_model', type: 'string', example: 'SM-S921B', nullable: true),
                                    new OA\Property(property: 'app_version', type: 'string', example: '1.0.0', nullable: true),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', example: '2026-02-15T10:30:00+07:00', nullable: true),
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
        $tokens = DeviceToken::where('user_id', $request->user()->id)
            ->orderByDesc('last_used_at')
            ->get();

        return response()->json([
            'data' => $tokens->map(fn ($token) => [
                'id' => $token->id,
                'platform' => $token->platform,
                'device_name' => $token->device_name,
                'device_model' => $token->device_model,
                'app_version' => $token->app_version,
                'is_active' => $token->is_active,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
            ]),
        ]);
    }

    #[OA\Post(
        path: '/device-tokens/register',
        summary: 'Daftarkan Device Token',
        description: 'Mendaftarkan device token baru untuk push notification atau memperbarui token yang sudah ada. Jika token sudah terdaftar, data akan diperbarui.',
        tags: ['DeviceToken'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'platform'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', maxLength: 500, example: 'dK8jH3kL...firebase_token', description: 'Firebase Cloud Messaging token'),
                    new OA\Property(property: 'platform', type: 'string', enum: ['android', 'ios', 'web'], example: 'android', description: 'Platform device'),
                    new OA\Property(property: 'device_name', type: 'string', maxLength: 255, example: 'Samsung Galaxy S24', description: 'Nama device (opsional)', nullable: true),
                    new OA\Property(property: 'device_model', type: 'string', maxLength: 255, example: 'SM-S921B', description: 'Model device (opsional)', nullable: true),
                    new OA\Property(property: 'app_version', type: 'string', maxLength: 50, example: '1.0.0', description: 'Versi aplikasi (opsional)', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Device token berhasil didaftarkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Device token registered successfully.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'platform', type: 'string', example: 'android'),
                                new OA\Property(property: 'device_name', type: 'string', example: 'Samsung Galaxy S24'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 200,
                description: 'Device token berhasil diperbarui (token sudah ada)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Device token updated successfully.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'platform', type: 'string', example: 'android'),
                                new OA\Property(property: 'device_name', type: 'string', example: 'Samsung Galaxy S24'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'in:android,ios,web'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        // Check if token already exists
        $existingToken = DeviceToken::where('token', $validated['token'])->first();

        if ($existingToken) {
            // Update existing token
            $existingToken->update([
                'user_id' => $user->id,
                'platform' => $validated['platform'],
                'device_name' => $validated['device_name'] ?? $existingToken->device_name,
                'device_model' => $validated['device_model'] ?? $existingToken->device_model,
                'app_version' => $validated['app_version'] ?? $existingToken->app_version,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            return response()->json([
                'message' => 'Device token updated successfully.',
                'data' => [
                    'id' => $existingToken->id,
                    'platform' => $existingToken->platform,
                    'device_name' => $existingToken->device_name,
                ],
            ]);
        }

        // Create new token
        $deviceToken = DeviceToken::create([
            'user_id' => $user->id,
            'token' => $validated['token'],
            'platform' => $validated['platform'],
            'device_name' => $validated['device_name'] ?? null,
            'device_model' => $validated['device_model'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'is_active' => true,
            'last_used_at' => now(),
        ]);

        return response()->json([
            'message' => 'Device token registered successfully.',
            'data' => [
                'id' => $deviceToken->id,
                'platform' => $deviceToken->platform,
                'device_name' => $deviceToken->device_name,
            ],
        ], 201);
    }

    #[OA\Delete(
        path: '/device-tokens/unregister',
        summary: 'Hapus Device Token',
        description: 'Menghapus device token dari sistem. Biasanya dipanggil saat user logout atau uninstall aplikasi.',
        tags: ['DeviceToken'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'dK8jH3kL...firebase_token', description: 'Firebase Cloud Messaging token yang akan dihapus'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Device token berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Device token unregistered successfully.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Device token tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Device token not found.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function unregister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $user = $request->user();

        $deviceToken = DeviceToken::where('token', $validated['token'])
            ->where('user_id', $user->id)
            ->first();

        if (! $deviceToken) {
            return response()->json([
                'message' => 'Device token not found.',
            ], 404);
        }

        $deviceToken->delete();

        return response()->json([
            'message' => 'Device token unregistered successfully.',
        ]);
    }

    #[OA\Post(
        path: '/device-tokens/refresh',
        summary: 'Refresh Device Token',
        description: 'Memperbarui device token dengan token baru. Dipanggil ketika FCM mengeluarkan token baru untuk device yang sama.',
        tags: ['DeviceToken'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['old_token', 'new_token'],
                properties: [
                    new OA\Property(property: 'old_token', type: 'string', example: 'old_firebase_token...', description: 'Token lama yang akan diganti'),
                    new OA\Property(property: 'new_token', type: 'string', maxLength: 500, example: 'new_firebase_token...', description: 'Token baru dari FCM'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Device token berhasil di-refresh',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Device token refreshed successfully.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'platform', type: 'string', example: 'android'),
                                new OA\Property(property: 'device_name', type: 'string', example: 'Samsung Galaxy S24'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Device token lama tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Device token not found.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'old_token' => ['required', 'string'],
            'new_token' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $deviceToken = DeviceToken::where('token', $validated['old_token'])
            ->where('user_id', $user->id)
            ->first();

        if (! $deviceToken) {
            return response()->json([
                'message' => 'Device token not found.',
            ], 404);
        }

        $deviceToken->update([
            'token' => $validated['new_token'],
            'last_used_at' => now(),
        ]);

        return response()->json([
            'message' => 'Device token refreshed successfully.',
            'data' => [
                'id' => $deviceToken->id,
                'platform' => $deviceToken->platform,
                'device_name' => $deviceToken->device_name,
            ],
        ]);
    }
}
