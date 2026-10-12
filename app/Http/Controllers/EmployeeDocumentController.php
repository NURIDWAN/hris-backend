<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    /**
     * Display documents for an employee.
     */
    public function index(Employee $employee): View
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id) {
            abort(404);
        }

        $documents = $employee->documents()
            ->orderBy('document_type')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('employee-documents.index', compact('employee', 'documents'));
    }

    /**
     * Show the form for creating a new document.
     */
    public function create(Employee $employee): View
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id) {
            abort(404);
        }

        $documentTypes = EmployeeDocument::getDocumentTypes();

        return view('employee-documents.create', compact('employee', 'documentTypes'));
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:'.implode(',', array_keys(EmployeeDocument::DOCUMENT_TYPES))],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:issue_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Store the file
        $file = $request->file('file');
        $path = $file->store("documents/{$tenant->id}/{$employee->id}", 'public');

        EmployeeDocument::create([
            'company_id' => $tenant->id,
            'employee_id' => $employee->id,
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'] ?? null,
            'document_name' => $validated['document_name'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'issue_date' => $validated['issue_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('employees.documents.index', $employee)
            ->with('success', 'Dokumen berhasil diunggah.');
    }

    /**
     * Display the specified document.
     */
    public function show(Employee $employee, EmployeeDocument $document): View
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id || $document->employee_id !== $employee->id) {
            abort(404);
        }

        return view('employee-documents.show', compact('employee', 'document'));
    }

    /**
     * Download the document file.
     */
    public function download(Employee $employee, EmployeeDocument $document): StreamedResponse
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id || $document->employee_id !== $employee->id) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => $document->mime_type]
        );
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id || $document->employee_id !== $employee->id) {
            abort(404);
        }

        // Soft delete (keeps file for audit purposes)
        $document->delete();

        return redirect()
            ->route('employees.documents.index', $employee)
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * Verify the document.
     */
    public function verify(Request $request, Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id || $document->employee_id !== $employee->id) {
            abort(404);
        }

        $document->verify($request->user());

        return redirect()
            ->back()
            ->with('success', 'Dokumen berhasil diverifikasi.');
    }

    /**
     * Unverify the document.
     */
    public function unverify(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id || $document->employee_id !== $employee->id) {
            abort(404);
        }

        $document->unverify();

        return redirect()
            ->back()
            ->with('success', 'Verifikasi dokumen dibatalkan.');
    }
}
