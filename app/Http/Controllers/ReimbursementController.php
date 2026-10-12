<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReimbursementController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        // Optimized eager loading - Laravel will deduplicate nested relations
        $query = Reimbursement::with(['employee.department', 'category', 'approver'])
            ->where('company_id', $tenant->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        $reimbursements = $query->latest()->paginate(20)->withQueryString();

        $categories = ReimbursementCategory::where('company_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('reimbursements.index', [
            'reimbursements' => $reimbursements,
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        $tenant = app('tenant');

        $employees = Employee::where('company_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $categories = ReimbursementCategory::where('company_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('reimbursements.create', [
            'employees' => $employees,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $category = ReimbursementCategory::findOrFail($request->category_id);

        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'category_id' => 'required|exists:reimbursement_categories,id',
            'amount' => [
                'required',
                'numeric',
                'min:1000',
                $category->max_amount ? 'max:'.$category->max_amount : '',
            ],
            'description' => 'required|string|max:1000',
            'expense_date' => 'required|date|before_or_equal:today',
            'receipt' => $category->requires_receipt ? 'required|image|max:5120' : 'nullable|image|max:5120',
        ];

        $validated = $request->validate($rules, [
            'amount.max' => 'Jumlah melebihi batas maksimum kategori ('.$category->formatted_max_amount.').',
            'receipt.required' => 'Bukti/struk wajib diunggah untuk kategori ini.',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        Reimbursement::create([
            'company_id' => $tenant->id,
            'employee_id' => $validated['employee_id'],
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'expense_date' => $validated['expense_date'],
            'receipt_path' => $receiptPath,
            'status' => Reimbursement::STATUS_PENDING,
        ]);

        return redirect()->route('reimbursements.index')
            ->with('success', 'Pengajuan reimbursement berhasil dibuat.');
    }

    public function show(Reimbursement $reimbursement): View
    {
        $tenant = app('tenant');

        if ($reimbursement->company_id !== $tenant->id) {
            abort(404);
        }

        $reimbursement->load(['employee', 'employee.department', 'employee.position', 'category', 'approver']);

        return view('reimbursements.show', [
            'reimbursement' => $reimbursement,
        ]);
    }

    public function approve(Reimbursement $reimbursement): RedirectResponse
    {
        $tenant = app('tenant');

        if ($reimbursement->company_id !== $tenant->id) {
            abort(404);
        }

        if (! $reimbursement->isPending()) {
            return back()->with('error', 'Hanya pengajuan dengan status pending yang dapat disetujui.');
        }

        $reimbursement->approve(auth()->id());

        return back()->with('success', 'Reimbursement berhasil disetujui.');
    }

    public function reject(Request $request, Reimbursement $reimbursement): RedirectResponse
    {
        $tenant = app('tenant');

        if ($reimbursement->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if (! $reimbursement->isPending()) {
            return back()->with('error', 'Hanya pengajuan dengan status pending yang dapat ditolak.');
        }

        $reimbursement->reject($validated['rejection_reason']);

        return back()->with('success', 'Reimbursement berhasil ditolak.');
    }

    public function pay(Request $request, Reimbursement $reimbursement): RedirectResponse
    {
        $tenant = app('tenant');

        if ($reimbursement->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:transfer,cash,payroll',
        ]);

        if (! $reimbursement->isApproved()) {
            return back()->with('error', 'Hanya reimbursement yang sudah disetujui yang dapat dibayar.');
        }

        $reimbursement->markAsPaid($validated['payment_method']);

        return back()->with('success', 'Pembayaran reimbursement berhasil dicatat.');
    }
}
