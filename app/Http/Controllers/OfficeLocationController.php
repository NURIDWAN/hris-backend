<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeLocationRequest;
use App\Http\Requests\UpdateOfficeLocationRequest;
use App\Models\Employee;
use App\Models\OfficeLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeLocationController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $query = OfficeLocation::where('company_id', $tenant->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $officeLocations = $query->orderBy('name')->paginate(15);

        return view('office-locations.index', compact('officeLocations'));
    }

    public function create(): View
    {
        return view('office-locations.create');
    }

    public function store(StoreOfficeLocationRequest $request): RedirectResponse
    {
        $tenant = app('tenant');

        OfficeLocation::create([
            'company_id' => $tenant->id,
            ...$request->validated(),
        ]);

        return redirect()->route('office-locations.index')
            ->with('success', 'Lokasi kantor berhasil ditambahkan.');
    }

    public function show(OfficeLocation $officeLocation): View
    {
        $tenant = app('tenant');

        if ($officeLocation->company_id !== $tenant->id) {
            abort(404);
        }

        $assignedEmployees = $officeLocation->employees()
            ->with(['department', 'position'])
            ->get();

        $availableEmployees = Employee::where('company_id', $tenant->id)
            ->whereNotIn('id', $assignedEmployees->pluck('id'))
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return view('office-locations.show', compact(
            'officeLocation',
            'assignedEmployees',
            'availableEmployees'
        ));
    }

    public function edit(OfficeLocation $officeLocation): View
    {
        $tenant = app('tenant');

        if ($officeLocation->company_id !== $tenant->id) {
            abort(404);
        }

        return view('office-locations.edit', compact('officeLocation'));
    }

    public function update(UpdateOfficeLocationRequest $request, OfficeLocation $officeLocation): RedirectResponse
    {
        $tenant = app('tenant');

        if ($officeLocation->company_id !== $tenant->id) {
            abort(404);
        }

        $officeLocation->update($request->validated());

        return redirect()->route('office-locations.index')
            ->with('success', 'Lokasi kantor berhasil diperbarui.');
    }

    public function destroy(OfficeLocation $officeLocation): RedirectResponse
    {
        $tenant = app('tenant');

        if ($officeLocation->company_id !== $tenant->id) {
            abort(404);
        }

        $officeLocation->delete();

        return redirect()->route('office-locations.index')
            ->with('success', 'Lokasi kantor berhasil dihapus.');
    }

    public function assignEmployees(Request $request, OfficeLocation $officeLocation): RedirectResponse
    {
        $tenant = app('tenant');

        if ($officeLocation->company_id !== $tenant->id) {
            abort(404);
        }

        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'primary_employee_id' => 'nullable|exists:employees,id',
        ]);

        $syncData = [];
        foreach ($request->employee_ids as $employeeId) {
            $syncData[$employeeId] = [
                'is_primary' => $employeeId == $request->primary_employee_id,
            ];
        }

        $officeLocation->employees()->syncWithoutDetaching($syncData);

        return redirect()->back()
            ->with('success', 'Karyawan berhasil ditambahkan ke lokasi kantor.');
    }

    public function removeEmployee(OfficeLocation $officeLocation, Employee $employee): RedirectResponse
    {
        $tenant = app('tenant');

        if ($officeLocation->company_id !== $tenant->id) {
            abort(404);
        }

        $officeLocation->employees()->detach($employee->id);

        return redirect()->back()
            ->with('success', 'Karyawan berhasil dihapus dari lokasi kantor.');
    }

    public function toggleStatus(OfficeLocation $officeLocation): RedirectResponse
    {
        $tenant = app('tenant');

        if ($officeLocation->company_id !== $tenant->id) {
            abort(404);
        }

        $officeLocation->update([
            'is_active' => ! $officeLocation->is_active,
        ]);

        $status = $officeLocation->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Lokasi kantor berhasil {$status}.");
    }
}
