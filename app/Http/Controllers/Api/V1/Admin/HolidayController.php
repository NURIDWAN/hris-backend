<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = Holiday::where('company_id', $companyId);

        if ($year = $request->input('year')) {
            $query->forYear((int) $year);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 50), 100);
        $holidays = $query->orderBy('date', 'asc')->paginate($perPage);

        return $this->paginatedResponse($holidays, 'Daftar hari libur berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => [
                'required',
                'date',
                Rule::unique('holidays', 'date')->where('company_id', $companyId),
            ],
            'type' => ['nullable', 'string', 'in:national,company,religious,cuti_bersama'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_recurring' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $holiday = Holiday::create([
            ...$validated,
            'company_id' => $companyId,
            'type' => $validated['type'] ?? 'national',
            'is_recurring' => $request->boolean('is_recurring'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'created_by' => $request->user()->id,
        ]);

        return $this->successResponse($holiday, 'Hari libur berhasil ditambahkan', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $holiday = Holiday::where('company_id', $companyId)->findOrFail($id);

        return $this->successResponse($holiday, 'Detail hari libur berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $holiday = Holiday::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'date' => [
                'sometimes',
                'required',
                'date',
                Rule::unique('holidays', 'date')->where('company_id', $companyId)->ignore($holiday->id),
            ],
            'type' => ['nullable', 'string', 'in:national,company,religious,cuti_bersama'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_recurring' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('is_recurring')) {
            $validated['is_recurring'] = $request->boolean('is_recurring');
        }
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $holiday->update($validated);

        return $this->successResponse($holiday->fresh(), 'Hari libur berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $holiday = Holiday::where('company_id', $companyId)->findOrFail($id);
        $holiday->delete();

        return $this->successResponse(null, 'Hari libur berhasil dihapus');
    }

    public function generate(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $year = $validated['year'];

        $nationalHolidays = [
            ['name' => 'Tahun Baru Masehi', 'date' => "{$year}-01-01", 'type' => 'national'],
            ['name' => 'Hari Buruh Internasional', 'date' => "{$year}-05-01", 'type' => 'national'],
            ['name' => 'Hari Lahir Pancasila', 'date' => "{$year}-06-01", 'type' => 'national'],
            ['name' => 'Hari Kemerdekaan RI', 'date' => "{$year}-08-17", 'type' => 'national'],
            ['name' => 'Hari Raya Natal', 'date' => "{$year}-12-25", 'type' => 'religious'],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($nationalHolidays as $item) {
            $exists = Holiday::where('company_id', $companyId)
                ->whereDate('date', $item['date'])
                ->exists();

            if (! $exists) {
                Holiday::create([
                    'company_id' => $companyId,
                    'name' => $item['name'],
                    'date' => $item['date'],
                    'type' => $item['type'],
                    'is_recurring' => true,
                    'is_active' => true,
                    'created_by' => $request->user()->id,
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        return $this->successResponse([
            'year' => $year,
            'created' => $created,
            'skipped' => $skipped,
        ], "Berhasil men-generate {$created} hari libur nasional ({$skipped} dilewati)");
    }
}
