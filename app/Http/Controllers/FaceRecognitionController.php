<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\FaceRecognitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FaceRecognitionController extends Controller
{
    public function __construct(
        protected FaceRecognitionService $faceRecognitionService
    ) {
    }

    /**
     * Display list of employees with face enrollment status.
     */
    public function index(): View
    {
        $tenant = app('tenant');

        $employees = Employee::with(['user', 'department', 'position', 'faceEmbedding'])
            ->where('company_id', $tenant->id)
            ->orderBy('full_name')
            ->paginate(15);

        return view('face-recognition.index', compact('employees'));
    }

    /**
     * Show face enrollment form for specific employee.
     */
    public function show(Employee $employee): View
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id) {
            abort(404);
        }

        $embedding = $employee->faceEmbedding()
            ->where('is_active', true)
            ->first();

        return view('face-recognition.show', compact('employee', 'embedding'));
    }

    /**
     * Store face enrollment for an employee.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'], // Max 5MB
            'embedding_data' => ['sometimes', 'json'],
        ]);

        // Store the photo
        $photoPath = $request->file('photo')->store(
            "face-enrollments/{$tenant->id}",
            'public'
        );

        // Deactivate existing enrollment if any
        $existingEmbedding = $employee->faceEmbedding;
        if ($existingEmbedding) {
            $existingEmbedding->update(['is_active' => false]);
        }

        // Parse embedding data from client or generate placeholder
        $embeddingData = [];
        if ($request->has('embedding_data')) {
            $embeddingData = json_decode($request->input('embedding_data'), true);
        }

        // Create new enrollment
        $this->faceRecognitionService->enrollFace(
            $employee,
            $embeddingData,
            $photoPath,
            $request->user(),
            $embeddingData['quality_score'] ?? null
        );

        return redirect()
            ->route('face-recognition.show', $employee)
            ->with('success', 'Wajah karyawan berhasil didaftarkan.');
    }

    /**
     * Remove face enrollment for an employee.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id) {
            abort(404);
        }

        // Hapus semua embedding aktif maupun tidak aktif
        $embeddings = $employee->faceEmbedding()->get();

        if ($embeddings->isEmpty()) {
            return redirect()
                ->route('employees.show', $employee)
                ->with('error', 'Karyawan ini belum memiliki pendaftaran wajah.');
        }

        foreach ($embeddings as $embedding) {
            // Hapus file foto jika ada
            if ($embedding->enrollment_photo) {
                Storage::disk('public')->delete($embedding->enrollment_photo);
            }
            // Hard delete dengan forceDelete karena model menggunakan SoftDeletes
            $embedding->forceDelete();
        }

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Data wajah karyawan berhasil direset. Karyawan perlu mendaftarkan wajah ulang saat absen.');
    }
}
