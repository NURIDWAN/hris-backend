<?php

namespace App\Http\Controllers;

use App\Models\ReimbursementCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReimbursementCategoryController extends Controller
{
    public function index(): View
    {
        $tenant = app('tenant');

        $categories = ReimbursementCategory::where('company_id', $tenant->id)
            ->withCount('reimbursements')
            ->orderBy('name')
            ->paginate(20);

        return view('reimbursement-categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('reimbursement-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reimbursement_categories')->where(function ($query) use ($tenant) {
                    return $query->where('company_id', $tenant->id);
                }),
            ],
            'description' => 'nullable|string|max:1000',
            'max_amount' => 'nullable|numeric|min:0',
            'requires_receipt' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        ReimbursementCategory::create([
            'company_id' => $tenant->id,
            ...$validated,
        ]);

        return redirect()->route('reimbursement-categories.index')
            ->with('success', 'Kategori reimbursement berhasil dibuat.');
    }

    public function edit(ReimbursementCategory $reimbursementCategory): View
    {
        $tenant = app('tenant');

        if ($reimbursementCategory->company_id !== $tenant->id) {
            abort(404);
        }

        return view('reimbursement-categories.edit', [
            'category' => $reimbursementCategory,
        ]);
    }

    public function update(Request $request, ReimbursementCategory $reimbursementCategory): RedirectResponse
    {
        $tenant = app('tenant');

        if ($reimbursementCategory->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reimbursement_categories')
                    ->where(function ($query) use ($tenant) {
                        return $query->where('company_id', $tenant->id);
                    })
                    ->ignore($reimbursementCategory->id),
            ],
            'description' => 'nullable|string|max:1000',
            'max_amount' => 'nullable|numeric|min:0',
            'requires_receipt' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        $reimbursementCategory->update($validated);

        return redirect()->route('reimbursement-categories.index')
            ->with('success', 'Kategori reimbursement berhasil diperbarui.');
    }

    public function destroy(ReimbursementCategory $reimbursementCategory): RedirectResponse
    {
        $tenant = app('tenant');

        if ($reimbursementCategory->company_id !== $tenant->id) {
            abort(404);
        }

        $reimbursementCategory->delete();

        return redirect()->route('reimbursement-categories.index')
            ->with('success', 'Kategori reimbursement berhasil dihapus.');
    }
}
