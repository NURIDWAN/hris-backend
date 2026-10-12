<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxForm1721A1Request;
use App\Models\Employee;
use App\Models\TaxForm1721A1;
use App\Services\TaxForm1721A1Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxForm1721A1Controller extends Controller
{
    public function __construct(
        protected TaxForm1721A1Service $taxFormService
    ) {}

    public function index(Request $request): View
    {
        $tenant = app('tenant');
        $companyId = $tenant->id;

        $query = TaxForm1721A1::with(['employee', 'generatedBy'])
            ->where('company_id', $companyId);

        // Filter by year
        if ($request->filled('year')) {
            $query->where('tax_year', $request->year);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $taxForms = $query->orderByDesc('tax_year')
            ->orderBy('employee_name')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();

        $years = $this->taxFormService->getAvailableYears($companyId);

        return view('tax-forms.1721a1.index', compact('taxForms', 'employees', 'years'));
    }

    public function create(Request $request): View
    {
        $tenant = app('tenant');
        $companyId = $tenant->id;

        $employees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $years = $this->taxFormService->getAvailableYears($companyId);

        // Add current year if not in list
        $currentYear = now()->year;
        if (! in_array($currentYear, $years)) {
            $years[] = $currentYear;
            sort($years);
        }

        return view('tax-forms.1721a1.create', compact('employees', 'years'));
    }

    public function store(StoreTaxForm1721A1Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $employee = Employee::where('company_id', $tenant->id)
            ->findOrFail($request->employee_id);

        // Check if already exists
        $exists = TaxForm1721A1::where('company_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->where('tax_year', $request->tax_year)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'employee_id' => 'Bukti potong untuk karyawan ini di tahun tersebut sudah ada.',
            ])->withInput();
        }

        $this->taxFormService->generateForEmployee(
            $employee,
            $request->tax_year,
            auth()->id()
        );

        return redirect()
            ->route('tax-forms.1721a1.index')
            ->with('success', 'Bukti Potong 1721-A1 berhasil dibuat.');
    }

    public function show(TaxForm1721A1 $taxForm1721a1): View
    {
        $tenant = app('tenant');

        if ($taxForm1721a1->company_id !== $tenant->id) {
            abort(404);
        }

        $taxForm1721a1->load(['employee', 'company', 'generatedBy']);

        return view('tax-forms.1721a1.show', [
            'taxForm' => $taxForm1721a1,
        ]);
    }

    public function pdf(TaxForm1721A1 $taxForm1721a1)
    {
        $tenant = app('tenant');

        if ($taxForm1721a1->company_id !== $tenant->id) {
            abort(404);
        }

        $taxForm1721a1->load(['employee', 'company']);

        $pdf = Pdf::loadView('tax-forms.1721a1.pdf', [
            'taxForm' => $taxForm1721a1,
        ]);

        $filename = "1721-A1-{$taxForm1721a1->employee_name}-{$taxForm1721a1->tax_year}.pdf";

        return $pdf->download($filename);
    }

    public function pdfBulk(Request $request)
    {
        $tenant = app('tenant');
        $year = $request->get('year', now()->year);

        $taxForms = TaxForm1721A1::with(['employee', 'company'])
            ->where('company_id', $tenant->id)
            ->where('tax_year', $year)
            ->orderBy('employee_name')
            ->get();

        if ($taxForms->isEmpty()) {
            return back()->with('error', 'Tidak ada bukti potong untuk tahun tersebut.');
        }

        $pdf = Pdf::loadView('tax-forms.1721a1.pdf-bulk', [
            'taxForms' => $taxForms,
            'year' => $year,
        ]);

        $filename = "1721-A1-Semua-Karyawan-{$year}.pdf";

        return $pdf->download($filename);
    }

    public function generateBulk(Request $request): RedirectResponse
    {
        $request->validate([
            'tax_year' => 'required|integer|min:2020|max:'.(now()->year + 1),
        ]);

        $tenant = app('tenant');

        $taxForms = $this->taxFormService->generateBulk(
            $tenant,
            $request->tax_year,
            auth()->id()
        );

        return redirect()
            ->route('tax-forms.1721a1.index')
            ->with('success', "Berhasil membuat {$taxForms->count()} Bukti Potong 1721-A1.");
    }

    public function regenerate(TaxForm1721A1 $taxForm1721a1): RedirectResponse
    {
        $tenant = app('tenant');

        if ($taxForm1721a1->company_id !== $tenant->id) {
            abort(404);
        }

        $this->taxFormService->regenerate($taxForm1721a1, auth()->id());

        return redirect()
            ->route('tax-forms.1721a1.show', $taxForm1721a1)
            ->with('success', 'Bukti Potong 1721-A1 berhasil diperbarui.');
    }

    public function destroy(TaxForm1721A1 $taxForm1721a1): RedirectResponse
    {
        $tenant = app('tenant');

        if ($taxForm1721a1->company_id !== $tenant->id) {
            abort(404);
        }

        $taxForm1721a1->delete();

        return redirect()
            ->route('tax-forms.1721a1.index')
            ->with('success', 'Bukti Potong 1721-A1 berhasil dihapus.');
    }
}
