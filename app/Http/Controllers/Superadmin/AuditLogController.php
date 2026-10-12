<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $activities = Activity::with(['causer', 'subject'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->event))
            ->when($request->filled('log_name'), fn ($q) => $q->where('log_name', $request->log_name))
            ->when($request->filled('search'), fn ($q) => $q->whereHas('causer', fn ($sub) => $sub->where('name', 'like', "%{$request->search}%")))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'total' => Activity::count(),
            'today' => Activity::whereDate('created_at', today())->count(),
            'created' => Activity::where('event', 'created')->count(),
            'updated' => Activity::where('event', 'updated')->count(),
            'deleted' => Activity::where('event', 'deleted')->count(),
        ];

        $companies = Company::orderBy('name')->pluck('name', 'id');

        $logNames = Activity::distinct()->pluck('log_name')->filter()->sort()->values();

        return view('superadmin.system.audit-logs.index', compact('activities', 'stats', 'companies', 'logNames'));
    }

    public function show(int $id): View
    {
        $activity = Activity::with(['causer', 'subject'])->findOrFail($id);

        return view('superadmin.system.audit-logs.show', compact('activity'));
    }
}
