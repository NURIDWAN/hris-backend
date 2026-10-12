<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkScheduleRequest;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $query = WorkSchedule::withCount('employees')
            ->where('company_id', $tenant->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $workSchedules = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('work-schedules.index', compact('workSchedules'));
    }

    public function create(): View
    {
        return view('work-schedules.create');
    }

    public function store(WorkScheduleRequest $request): RedirectResponse
    {
        $tenant = app('tenant');

        // If this schedule is set as default, remove default from others
        if ($request->boolean('is_default')) {
            WorkSchedule::where('company_id', $tenant->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        WorkSchedule::create([
            ...$request->validated(),
            'company_id' => $tenant->id,
        ]);

        return redirect()->route('work-schedules.index')
            ->with('success', 'Jadwal kerja berhasil ditambahkan.');
    }

    public function show(WorkSchedule $workSchedule): View
    {
        $tenant = app('tenant');

        if ($workSchedule->company_id !== $tenant->id) {
            abort(404);
        }

        $workSchedule->loadCount('employees');

        return view('work-schedules.show', compact('workSchedule'));
    }

    public function edit(WorkSchedule $workSchedule): View
    {
        $tenant = app('tenant');

        if ($workSchedule->company_id !== $tenant->id) {
            abort(404);
        }

        return view('work-schedules.edit', compact('workSchedule'));
    }

    public function update(WorkScheduleRequest $request, WorkSchedule $workSchedule): RedirectResponse
    {
        $tenant = app('tenant');

        if ($workSchedule->company_id !== $tenant->id) {
            abort(404);
        }

        // If this schedule is set as default, remove default from others
        if ($request->boolean('is_default') && !$workSchedule->is_default) {
            WorkSchedule::where('company_id', $tenant->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $workSchedule->update($request->validated());

        return redirect()->route('work-schedules.index')
            ->with('success', 'Jadwal kerja berhasil diperbarui.');
    }

    public function destroy(WorkSchedule $workSchedule): RedirectResponse
    {
        $tenant = app('tenant');

        if ($workSchedule->company_id !== $tenant->id) {
            abort(404);
        }

        if ($workSchedule->is_default) {
            return redirect()->route('work-schedules.index')
                ->with('error', 'Jadwal kerja default tidak dapat dihapus.');
        }

        if ($workSchedule->employees()->count() > 0) {
            return redirect()->route('work-schedules.index')
                ->with('error', 'Jadwal kerja tidak dapat dihapus karena masih digunakan oleh karyawan.');
        }

        $workSchedule->delete();

        return redirect()->route('work-schedules.index')
            ->with('success', 'Jadwal kerja berhasil dihapus.');
    }
}
