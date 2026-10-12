<?php

namespace App\Http\Controllers\Import;

use App\Exports\Templates\WorkScheduleTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\WorkScheduleImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class WorkScheduleImportController extends Controller
{
    public function index(): View
    {
        return view('imports.work-schedules.index');
    }

    public function template()
    {
        return Excel::download(new WorkScheduleTemplateExport, 'template_jadwal_kerja.xlsx');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $tenant = app('tenant');

        try {
            $import = new WorkScheduleImport($tenant->id);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $skipCount = $import->getSkipCount();
            $errors = $import->getErrors();

            $message = "Berhasil mengimpor {$successCount} jadwal kerja.";
            if ($skipCount > 0) {
                $message .= " {$skipCount} data dilewati.";
            }

            if (count($errors) > 0) {
                return redirect()->route('imports.work-schedules.index')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            return redirect()->route('imports.work-schedules.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('imports.work-schedules.index')
                ->with('error', 'Gagal mengimpor data: '.$e->getMessage());
        }
    }
}
