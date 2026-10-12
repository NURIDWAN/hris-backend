<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AnnouncementController extends Controller
{
    #[OA\Get(
        path: '/announcements',
        summary: 'Daftar Pengumuman',
        description: 'Menampilkan daftar pengumuman yang aktif dan ditargetkan untuk user yang sedang login. Pengumuman diurutkan berdasarkan status pin, prioritas, dan tanggal publikasi.',
        security: [['sanctum' => []]],
        tags: ['Announcement'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Nomor halaman untuk pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar pengumuman berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Libur Tahun Baru 2026'),
                                    new OA\Property(property: 'content', type: 'string', example: 'Kantor akan libur pada tanggal 1 Januari 2026'),
                                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'normal', 'high', 'urgent'], example: 'high'),
                                    new OA\Property(property: 'priority_label', type: 'string', example: 'Tinggi'),
                                    new OA\Property(property: 'is_pinned', type: 'boolean', example: true),
                                    new OA\Property(property: 'is_read', type: 'boolean', example: false),
                                    new OA\Property(property: 'published_at', type: 'string', format: 'date-time', example: '2026-01-15 10:00:00'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-01-15 09:30:00'),
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
                description: 'Unauthorized - Token tidak valid atau kadaluarsa'
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        $announcements = Announcement::where('company_id', $company->id)
            ->active()
            ->forUser($user)
            ->orderByDesc('is_pinned')
            ->orderByPriority()
            ->orderByDesc('published_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $announcements->map(function ($announcement) use ($user) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'priority' => $announcement->priority,
                    'priority_label' => $announcement->priority_label,
                    'is_pinned' => $announcement->is_pinned,
                    'is_read' => $announcement->isReadBy($user->id),
                    'published_at' => $announcement->published_at?->toDateTimeString(),
                    'created_at' => $announcement->created_at->toDateTimeString(),
                ];
            }),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/announcements/{id}',
        summary: 'Detail Pengumuman',
        description: 'Menampilkan detail lengkap pengumuman termasuk informasi pembuat pengumuman. Hanya dapat diakses oleh user yang menjadi target pengumuman tersebut.',
        security: [['sanctum' => []]],
        tags: ['Announcement'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID pengumuman',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail pengumuman berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'title', type: 'string', example: 'Libur Tahun Baru 2026'),
                                new OA\Property(property: 'content', type: 'string', example: 'Kantor akan libur pada tanggal 1 Januari 2026'),
                                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'normal', 'high', 'urgent'], example: 'high'),
                                new OA\Property(property: 'priority_label', type: 'string', example: 'Tinggi'),
                                new OA\Property(property: 'is_pinned', type: 'boolean', example: true),
                                new OA\Property(property: 'is_read', type: 'boolean', example: false),
                                new OA\Property(property: 'published_at', type: 'string', format: 'date-time', example: '2026-01-15 10:00:00'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-01-15 09:30:00'),
                                new OA\Property(
                                    property: 'creator',
                                    properties: [
                                        new OA\Property(property: 'name', type: 'string', example: 'Admin HRD'),
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
                response: 401,
                description: 'Unauthorized - Token tidak valid atau kadaluarsa'
            ),
            new OA\Response(
                response: 404,
                description: 'Pengumuman tidak ditemukan atau tidak dapat diakses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengumuman tidak ditemukan.'),
                    ]
                )
            ),
        ]
    )]
    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        // Check if announcement belongs to company and is accessible to user
        if ($announcement->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        if (! $announcement->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        // Check if announcement is targeted to this user
        $isTargeted = Announcement::where('id', $announcement->id)
            ->forUser($user)
            ->exists();

        if (! $isTargeted) {
            return response()->json([
                'success' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        $announcement->load('creator');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'priority' => $announcement->priority,
                'priority_label' => $announcement->priority_label,
                'is_pinned' => $announcement->is_pinned,
                'is_read' => $announcement->isReadBy($user->id),
                'published_at' => $announcement->published_at?->toDateTimeString(),
                'created_at' => $announcement->created_at->toDateTimeString(),
                'creator' => [
                    'name' => $announcement->creator?->name,
                ],
            ],
        ]);
    }

    #[OA\Post(
        path: '/announcements/{id}/read',
        summary: 'Tandai Pengumuman Sudah Dibaca',
        description: 'Menandai pengumuman tertentu sebagai sudah dibaca oleh user yang sedang login. Setelah ditandai, pengumuman tidak akan muncul lagi di counter pengumuman yang belum dibaca.',
        security: [['sanctum' => []]],
        tags: ['Announcement'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID pengumuman',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pengumuman berhasil ditandai sudah dibaca',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengumuman ditandai sudah dibaca.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Token tidak valid atau kadaluarsa'
            ),
            new OA\Response(
                response: 404,
                description: 'Pengumuman tidak ditemukan atau tidak dapat diakses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengumuman tidak ditemukan.'),
                    ]
                )
            ),
        ]
    )]
    public function markAsRead(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        // Validate announcement
        if ($announcement->company_id !== $company->id || ! $announcement->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        $announcement->markAsRead($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman ditandai sudah dibaca.',
        ]);
    }

    #[OA\Get(
        path: '/announcements/unread-count',
        summary: 'Jumlah Pengumuman Belum Dibaca',
        description: 'Menghitung jumlah pengumuman yang belum dibaca oleh user yang sedang login. Endpoint ini berguna untuk menampilkan badge notifikasi di aplikasi mobile.',
        security: [['sanctum' => []]],
        tags: ['Announcement'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Jumlah pengumuman belum dibaca berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'count', type: 'integer', example: 5),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Token tidak valid atau kadaluarsa'
            ),
        ]
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        $count = Announcement::where('company_id', $company->id)
            ->active()
            ->forUser($user)
            ->whereDoesntHave('readers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }
}
