<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $query = Activity::with(['causer', 'subject'])
            ->forCompany($tenant->id)
            ->orderBy('created_at', 'desc');

        // Filter by log name
        if ($request->filled('log_name')) {
            $query->inLog($request->log_name);
        }

        // Filter by event
        if ($request->filled('event')) {
            $query->forEvent($request->event);
        }

        // Filter by causer
        if ($request->filled('causer_id')) {
            $query->where('causer_type', User::class)
                ->where('causer_id', $request->causer_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in description
        if ($request->filled('search')) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $activities = $query->paginate(15)->withQueryString();

        // Get available log names for filter
        $logNames = Activity::forCompany($tenant->id)
            ->distinct()
            ->pluck('log_name')
            ->filter()
            ->sort()
            ->values();

        // Get users for causer filter
        $users = User::where('company_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('activity-logs.index', compact('activities', 'logNames', 'users'));
    }

    /**
     * Display the specified activity log.
     */
    public function show(Activity $activity): View
    {
        $tenant = app('tenant');

        if ($activity->company_id !== $tenant->id) {
            abort(404);
        }

        $activity->load(['causer', 'subject']);

        return view('activity-logs.show', compact('activity'));
    }
}
