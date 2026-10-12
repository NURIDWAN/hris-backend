<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $tenant = app('tenant');
        $user = auth()->user();

        $announcements = Announcement::with(['creator'])
            ->where('company_id', $tenant->id)
            ->active()
            ->forUser($user)
            ->orderByDesc('is_pinned')
            ->orderByPriority()
            ->orderByDesc('published_at')
            ->paginate(15);

        return view('portal.announcements.index', compact('announcements'));
    }

    public function show(Announcement $announcement): View
    {
        $tenant = app('tenant');
        $user = auth()->user();

        if ($announcement->company_id !== $tenant->id) {
            abort(404);
        }

        // Check if announcement is active (published and not expired)
        if (! $announcement->is_active) {
            abort(404);
        }

        // Check if user can see this announcement
        $canView = Announcement::where('id', $announcement->id)
            ->where('company_id', $tenant->id)
            ->active()
            ->forUser($user)
            ->exists();

        if (! $canView) {
            abort(404);
        }

        // Mark as read
        $announcement->markAsRead($user->id);

        $announcement->load(['creator']);

        return view('portal.announcements.show', compact('announcement'));
    }
}
