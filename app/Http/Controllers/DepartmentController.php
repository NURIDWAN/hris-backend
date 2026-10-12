<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $query = Department::with(['parent', 'positions'])
            ->withCount('employees')
            ->where('company_id', $tenant->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $departments = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        $tenant = app('tenant');
        $departments = Department::where('company_id', $tenant->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('departments.create', compact('departments'));
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $tenant = app('tenant');

        Department::create([
            ...$request->validated(),
            'company_id' => $tenant->id,
        ]);

        return redirect()->route('departments.index')
            ->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function edit(Department $department): View
    {
        $tenant = app('tenant');

        if ($department->company_id !== $tenant->id) {
            abort(404);
        }

        $departments = Department::where('company_id', $tenant->id)
            ->where('id', '!=', $department->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('departments.edit', compact('department', 'departments'));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $tenant = app('tenant');

        if ($department->company_id !== $tenant->id) {
            abort(404);
        }

        $department->update($request->validated());

        return redirect()->route('departments.index')
            ->with('success', 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $tenant = app('tenant');

        if ($department->company_id !== $tenant->id) {
            abort(404);
        }

        if ($department->employees()->count() > 0) {
            return redirect()->route('departments.index')
                ->with('error', 'Departemen tidak dapat dihapus karena masih memiliki karyawan.');
        }

        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Departemen berhasil dihapus.');
    }
}
