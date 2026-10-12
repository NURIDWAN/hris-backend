<?php

namespace App\Http\Controllers;

use App\Exports\Spt1721Export;
use App\Models\Spt1721;
use App\Services\Spt1721Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller untuk SPT Tahunan 1721
 */
class Spt1721Controller extends Controller
{
    public function __construct(
        private Spt1721Service $sptService
    ) {}

    /**
     * Daftar SPT 1721
     */
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $query = Spt1721::where('company_id', $tenant->id)
            ->with(['createdBy', 'submittedBy']);

        // Filter by year
        if ($request->filled('year')) {
            $query->where('tax_year', $request->year);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $spts = $query->orderBy('tax_year', 'desc')
            ->orderBy('pembetulan_ke', 'desc')
            ->paginate(15);

        // Available years for filter
        $years = Spt1721::where('company_id', $tenant->id)
            ->distinct()
            ->orderBy('tax_year', 'desc')
            ->pluck('tax_year');

        return view('spt-1721.index', compact('spts', 'years'));
    }

    /**
     * Form buat SPT baru
     */
    public function create(): View
    {
        $tenant = app('tenant');

        // Get existing years
        $existingYears = Spt1721::where('company_id', $tenant->id)
            ->where('spt_type', 'normal')
            ->pluck('tax_year')
            ->toArray();

        // Available years (current year and 2 previous)
        $currentYear = now()->year;
        $availableYears = [];
        for ($y = $currentYear; $y >= $currentYear - 2; $y--) {
            if (! in_array($y, $existingYears)) {
                $availableYears[] = $y;
            }
        }

        return view('spt-1721.create', compact('availableYears', 'existingYears'));
    }

    /**
     * Simpan SPT baru
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'tax_year' => 'required|integer|min:2020|max:'.(now()->year),
        ]);

        $tenant = app('tenant');

        // Check if already exists
        $existing = Spt1721::where('company_id', $tenant->id)
            ->where('tax_year', $request->tax_year)
            ->where('spt_type', 'normal')
            ->exists();

        if ($existing) {
            return back()->with('error', "SPT tahun {$request->tax_year} sudah ada.");
        }

        $spt = $this->sptService->createSpt(
            $tenant,
            $request->tax_year,
            auth()->id()
        );

        return redirect()->route('spt-1721.show', $spt)
            ->with('success', "SPT 1721 tahun {$request->tax_year} berhasil dibuat.");
    }

    /**
     * Detail SPT
     */
    public function show(Spt1721 $spt1721): View
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        $spt1721->load(['monthlyData', 'employees.employee', 'employees.taxForm']);

        // Summary by PTKP status
        $ptkpSummary = $spt1721->employees()
            ->selectRaw('ptkp_status, COUNT(*) as count, SUM(pph21_terutang) as total_pph')
            ->groupBy('ptkp_status')
            ->get();

        return view('spt-1721.show', compact('spt1721', 'ptkpSummary'));
    }

    /**
     * Hitung ulang SPT dari payroll
     */
    public function calculate(Spt1721 $spt1721): RedirectResponse
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        if (! $spt1721->canEdit()) {
            return back()->with('error', 'SPT sudah tidak dapat dihitung ulang.');
        }

        $this->sptService->calculateFromPayroll($spt1721);

        return back()->with('success', 'SPT berhasil dihitung ulang dari data payroll.');
    }

    /**
     * Sync dengan bukti potong 1721-A1
     */
    public function syncTaxForms(Spt1721 $spt1721): RedirectResponse
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        if (! $spt1721->canEdit()) {
            return back()->with('error', 'SPT sudah tidak dapat diubah.');
        }

        $this->sptService->syncEmployeesFromTaxForms($spt1721);
        $spt1721->recalculate();

        return back()->with('success', 'Data karyawan berhasil disinkronisasi dari bukti potong 1721-A1.');
    }

    /**
     * Submit SPT (tandai sudah disetor)
     */
    public function submit(Request $request, Spt1721 $spt1721): RedirectResponse
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        if (! $spt1721->canSubmit()) {
            return back()->with('error', 'SPT belum dapat disetor. Harap hitung terlebih dahulu.');
        }

        $request->validate([
            'ntpn' => 'required|string|size:16',
        ]);

        $this->sptService->submitSpt($spt1721, $request->ntpn, auth()->id());

        return back()->with('success', 'SPT berhasil ditandai sebagai sudah disetor.');
    }

    /**
     * Report SPT (tandai sudah dilaporkan)
     */
    public function report(Request $request, Spt1721 $spt1721): RedirectResponse
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        if (! $spt1721->canReport()) {
            return back()->with('error', 'SPT belum dapat dilaporkan. Harap setor terlebih dahulu.');
        }

        $request->validate([
            'submission_receipt' => 'required|string|max:50',
        ]);

        $this->sptService->reportSpt($spt1721, $request->submission_receipt);

        return back()->with('success', 'SPT berhasil ditandai sebagai sudah dilaporkan.');
    }

    /**
     * Buat pembetulan
     */
    public function createPembetulan(Spt1721 $spt1721): RedirectResponse
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        $pembetulan = $this->sptService->createPembetulan($spt1721, auth()->id());

        return redirect()->route('spt-1721.show', $pembetulan)
            ->with('success', "SPT Pembetulan ke-{$pembetulan->pembetulan_ke} berhasil dibuat.");
    }

    /**
     * Update data penandatangan
     */
    public function updateSigner(Request $request, Spt1721 $spt1721): RedirectResponse
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_npwp' => 'nullable|string|max:20',
            'signer_position' => 'required|string|max:100',
            'sign_date' => 'required|date',
            'sign_place' => 'required|string|max:100',
        ]);

        $spt1721->update($request->only([
            'signer_name', 'signer_npwp', 'signer_position', 'sign_date', 'sign_place',
        ]));

        return back()->with('success', 'Data penandatangan berhasil diperbarui.');
    }

    /**
     * Export Excel untuk e-SPT
     */
    public function exportExcel(Spt1721 $spt1721, ?string $type = null): BinaryFileResponse
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        $validTypes = ['induk', 'lampiran_i', 'lampiran_ii', null];
        if (! in_array($type, $validTypes)) {
            abort(404, 'Tipe export tidak valid.');
        }

        $export = new Spt1721Export($spt1721, $type);

        return Excel::download($export, $export->getFilename());
    }

    /**
     * Delete SPT (hanya draft)
     */
    public function destroy(Spt1721 $spt1721): RedirectResponse
    {
        $tenant = app('tenant');

        if ($spt1721->company_id !== $tenant->id) {
            abort(404);
        }

        if ($spt1721->status !== 'draft') {
            return back()->with('error', 'Hanya SPT dengan status draft yang dapat dihapus.');
        }

        $year = $spt1721->tax_year;
        $spt1721->delete();

        return redirect()->route('spt-1721.index')
            ->with('success', "SPT tahun {$year} berhasil dihapus.");
    }
}
