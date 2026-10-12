<?php

namespace App\Http\Controllers\Import;

use App\Exports\Templates\PositionTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\PositionImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PositionImportController extends Controller
{
    public function index(): View
    {
        return view('imports.positions.index');
    }

    public function template()
    {
        return Excel::download(new PositionTemplateExport, 'template_jabatan.xlsx');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $tenant = app('tenant');

        try {
            $import = new PositionImport($tenant->id);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $skipCount = $import->getSkipCount();
            $errors = $import->getErrors();

            $message = "Berhasil mengimpor {$successCount} jabatan.";
            if ($skipCount > 0) {
                $message .= " {$skipCount} data dilewati.";
            }

            if (count($errors) > 0) {
                return redirect()->route('imports.positions.index')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            return redirect()->route('imports.positions.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('imports.positions.index')
                ->with('error', 'Gagal mengimpor data: '.$e->getMessage());
        }
    }
}
