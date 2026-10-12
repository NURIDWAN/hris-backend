<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $tenant = app('tenant');

        $announcements = Announcement::with(['creator'])
            ->where('company_id', $tenant->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        $tenant = app('tenant');

        $departments = Department::where('company_id', $tenant->id)->orderBy('name')->get();
        $positions = Position::where('company_id', $tenant->id)->orderBy('name')->get();
        $employees = Employee::with('user')
            ->where('company_id', $tenant->id)
            ->orderBy('employee_id')
            ->get();

        return view('announcements.create', compact('departments', 'positions', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'target_audience' => 'required|in:all,department,position,specific',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
            'is_pinned' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $announcement = Announcement::create([
            'company_id' => $tenant->id,
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'target_audience' => $validated['target_audience'],
            'target_ids' => $validated['target_ids'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Pengumuman berhasil dibuat.');
    }

    public function show(Announcement $announcement): View
    {
        $tenant = app('tenant');

        if ($announcement->company_id !== $tenant->id) {
            abort(404);
        }

        $announcement->load(['creator', 'readers']);

        return view('announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement): View
    {
        $tenant = app('tenant');

        if ($announcement->company_id !== $tenant->id) {
            abort(404);
        }

        $departments = Department::where('company_id', $tenant->id)->orderBy('name')->get();
        $positions = Position::where('company_id', $tenant->id)->orderBy('name')->get();
        $employees = Employee::with('user')
            ->where('company_id', $tenant->id)
            ->orderBy('employee_id')
            ->get();

        return view('announcements.edit', compact('announcement', 'departments', 'positions', 'employees'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $tenant = app('tenant');

        if ($announcement->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'target_audience' => 'required|in:all,department,position,specific',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
            'is_pinned' => 'nullable|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'target_audience' => $validated['target_audience'],
            'target_ids' => $validated['target_ids'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $tenant = app('tenant');

        if ($announcement->company_id !== $tenant->id) {
            abort(404);
        }

        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        $tenant = app('tenant');

        if ($announcement->company_id !== $tenant->id) {
            abort(404);
        }

        $announcement->publish();

        return redirect()->back()
            ->with('success', 'Pengumuman berhasil dipublikasikan.');
    }

    public function unpublish(Announcement $announcement): RedirectResponse
    {
        $tenant = app('tenant');

        if ($announcement->company_id !== $tenant->id) {
            abort(404);
        }

        $announcement->unpublish();

        return redirect()->back()
            ->with('success', 'Pengumuman berhasil di-unpublish.');
    }
}
