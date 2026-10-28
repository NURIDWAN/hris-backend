<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = Announcement::with('creator:id,name,email')
            ->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_published') && $request->input('is_published') !== '') {
            $query->where('is_published', $request->boolean('is_published'));
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $announcements = $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($announcements, 'Daftar pengumuman berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'target_audience' => ['nullable', 'in:all,department,position,specific'],
            'target_ids' => ['nullable', 'array'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ]);

        $isPublished = $request->has('is_published') ? $request->boolean('is_published') : true;

        $announcement = Announcement::create([
            ...$validated,
            'company_id' => $companyId,
            'created_by' => $request->user()->id,
            'priority' => $validated['priority'] ?? Announcement::PRIORITY_NORMAL,
            'target_audience' => $validated['target_audience'] ?? Announcement::TARGET_ALL,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_published' => $isPublished,
            'published_at' => $isPublished ? ($validated['published_at'] ?? now()) : null,
        ]);

        return $this->successResponse($announcement->load('creator:id,name,email'), 'Pengumuman berhasil dibuat', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $announcement = Announcement::with('creator:id,name,email')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($announcement, 'Detail pengumuman berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'target_audience' => ['nullable', 'in:all,department,position,specific'],
            'target_ids' => ['nullable', 'array'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        if ($request->has('is_pinned')) {
            $validated['is_pinned'] = $request->boolean('is_pinned');
        }
        if ($request->has('is_published')) {
            $validated['is_published'] = $request->boolean('is_published');
            if ($validated['is_published'] && empty($announcement->published_at)) {
                $validated['published_at'] = now();
            }
        }

        $announcement->update($validated);

        return $this->successResponse($announcement->fresh('creator:id,name,email'), 'Pengumuman berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);
        $announcement->delete();

        return $this->successResponse(null, 'Pengumuman berhasil dihapus');
    }

    public function publish(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);
        $announcement->update([
            'is_published' => true,
            'published_at' => $announcement->published_at ?? now(),
        ]);

        return $this->successResponse($announcement, 'Pengumuman berhasil dipublikasikan');
    }

    public function unpublish(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $announcement = Announcement::where('company_id', $companyId)->findOrFail($id);
        $announcement->update(['is_published' => false]);

        return $this->successResponse($announcement, 'Pengumuman berhasil ditarik');
    }
}
