<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReimbursementController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $reimbursements = Reimbursement::with('category')
            ->where('employee_id', $employee->id)
            ->latest()
            ->paginate(15);

        return view('portal.reimbursements.index', [
            'reimbursements' => $reimbursements,
            'employee' => $employee,
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $categories = ReimbursementCategory::where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('portal.reimbursements.create', [
            'employee' => $employee,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $category = ReimbursementCategory::findOrFail($request->category_id);

        $rules = [
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
            'amount.max' => 'Jumlah melebihi batas maksimum kategori.',
            'receipt.required' => 'Bukti/struk wajib diunggah untuk kategori ini.',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        Reimbursement::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'expense_date' => $validated['expense_date'],
            'receipt_path' => $receiptPath,
            'status' => Reimbursement::STATUS_PENDING,
        ]);

        return redirect()->route('portal.reimbursements.index')
            ->with('success', 'Pengajuan reimbursement berhasil diajukan.');
    }

    public function show(Reimbursement $reimbursement): View
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        if ($reimbursement->employee_id !== $employee->id) {
            abort(403);
        }

        $reimbursement->load('category');

        return view('portal.reimbursements.show', [
            'reimbursement' => $reimbursement,
            'employee' => $employee,
        ]);
    }
}
