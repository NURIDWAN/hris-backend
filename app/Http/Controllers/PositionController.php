<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionRequest;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $query = Position::with('department')
            ->withCount('employees')
            ->where('company_id', $tenant->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $positions = $query->orderBy('name')->paginate(15)->withQueryString();

        $departments = Department::where('company_id', $tenant->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('positions.index', compact('positions', 'departments'));
    }

    public function create(): View
    {
        $tenant = app('tenant');
        $departments = Department::where('company_id', $tenant->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('positions.create', compact('departments'));
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        $tenant = app('tenant');

        Position::create([
            ...$request->validated(),
            'company_id' => $tenant->id,
        ]);

        return redirect()->route('positions.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Position $position): View
    {
        $tenant = app('tenant');

        if ($position->company_id !== $tenant->id) {
            abort(404);
        }

        $departments = Department::where('company_id', $tenant->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('positions.edit', compact('position', 'departments'));
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $tenant = app('tenant');

        if ($position->company_id !== $tenant->id) {
            abort(404);
        }

        $position->update($request->validated());

        return redirect()->route('positions.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $tenant = app('tenant');

        if ($position->company_id !== $tenant->id) {
            abort(404);
        }

        if ($position->employees()->count() > 0) {
            return redirect()->route('positions.index')
                ->with('error', 'Jabatan tidak dapat dihapus karena masih memiliki karyawan.');
        }

        $position->delete();

        return redirect()->route('positions.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }
}
