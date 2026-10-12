<?php

namespace App\Http\Controllers\Import;

use App\Exports\Templates\HolidayTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\HolidayImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class HolidayImportController extends Controller
{
    public function index(): View
    {
        return view('imports.holidays.index');
    }

    public function template()
    {
        return Excel::download(new HolidayTemplateExport, 'template_hari_libur.xlsx');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $tenant = app('tenant');

        try {
            $import = new HolidayImport($tenant->id, auth()->id());
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $skipCount = $import->getSkipCount();
            $errors = $import->getErrors();

            $message = "Berhasil mengimpor {$successCount} hari libur.";
            if ($skipCount > 0) {
                $message .= " {$skipCount} data dilewati.";
            }

            if (count($errors) > 0) {
                return redirect()->route('imports.holidays.index')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            return redirect()->route('imports.holidays.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('imports.holidays.index')
                ->with('error', 'Gagal mengimpor data: '.$e->getMessage());
        }
    }
}
