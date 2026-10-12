<?php

namespace App\Http\Controllers;

use App\Services\DemoDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemoSettingController extends Controller
{
    public function __construct(
        protected DemoDataService $demoDataService
    ) {}

    public function index(): View
    {
        $company = auth()->user()->company;

        $stats = [
            'employees' => $company->employees()->count(),
            'departments' => $company->departments()->count(),
            'positions' => $company->positions()->count(),
        ];

        return view('demo-settings.index', compact('company', 'stats'));
    }

    public function switchToProduction(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $company = auth()->user()->company;

        if (! $company->is_demo_mode) {
            return redirect()->route('demo.settings')
                ->with('error', 'Perusahaan sudah dalam mode produksi.');
        }

        $this->demoDataService->resetToProduction($company);

        return redirect()->route('dashboard')
            ->with('success', 'Selamat! Data demo telah dihapus. Anda sekarang dapat mulai memasukkan data perusahaan yang sebenarnya.');
    }

    public function resetDemoData(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $company = auth()->user()->company;

        if (! $company->is_demo_mode) {
            return redirect()->route('demo.settings')
                ->with('error', 'Fitur ini hanya tersedia dalam mode demo.');
        }

        // Reset and reseed
        $this->demoDataService->resetToProduction($company);
        $this->demoDataService->seedDemoData($company);

        return redirect()->route('dashboard')
            ->with('success', 'Data demo telah direset ulang.');
    }
}
