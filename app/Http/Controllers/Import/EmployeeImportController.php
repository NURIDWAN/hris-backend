<?php

namespace App\Http\Controllers\Import;

use App\Exports\Templates\EmployeeTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\EmployeeImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeImportController extends Controller
{
    public function index(): View
    {
        return view('imports.employees.index');
    }

    public function template()
    {
        return Excel::download(new EmployeeTemplateExport, 'template_karyawan.xlsx');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $tenant = app('tenant');

        try {
            $importId = uniqid('emp_import_');
            $import = new EmployeeImport($tenant->id, $importId);
            $import->initializeImport();

            Excel::queueImport($import, $request->file('file'));

            return redirect()->route('imports.employees.index')
                ->with('info', 'Import sedang diproses di background. Silakan tunggu beberapa saat.')
                ->with('import_id', $importId);
        } catch (\Exception $e) {
            return redirect()->route('imports.employees.index')
                ->with('error', 'Gagal memulai import: '.$e->getMessage());
        }
    }

    public function status(string $importId): JsonResponse
    {
        $status = EmployeeImport::getImportStatus($importId);

        if (! $status) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Import tidak ditemukan.',
            ], 404);
        }

        return response()->json($status);
    }
}
