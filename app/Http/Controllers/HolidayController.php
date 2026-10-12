<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;
        $currentYear = now()->year;

        $query = Holiday::where('company_id', $companyId);

        // Filter by year
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        } else {
            $query->whereYear('date', $currentYear);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $holidays = $query->orderBy('date')->paginate(15)->withQueryString();

        // Get available years for filter
        $years = Holiday::where('company_id', $companyId)
            ->get()
            ->map(fn ($h) => $h->date->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        // Add current year if not in list
        if (! in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        return view('holidays.index', compact('holidays', 'years', 'currentYear'));
    }

    public function create(): View
    {
        return view('holidays.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($companyId) {
                    $exists = Holiday::where('company_id', $companyId)
                        ->whereDate('date', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Tanggal ini sudah terdaftar sebagai hari libur.');
                    }
                },
            ],
            'type' => ['required', 'in:national,company,religious'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_recurring' => ['nullable', 'boolean'],
        ]);

        Holiday::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'date' => $validated['date'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_recurring' => $validated['is_recurring'] ?? false,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('holidays.index')
            ->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function edit(Holiday $holiday): View|RedirectResponse
    {
        if ($holiday->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        return view('holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        if ($holiday->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($companyId, $holiday) {
                    $exists = Holiday::where('company_id', $companyId)
                        ->whereDate('date', $value)
                        ->where('id', '!=', $holiday->id)
                        ->exists();
                    if ($exists) {
                        $fail('Tanggal ini sudah terdaftar sebagai hari libur.');
                    }
                },
            ],
            'type' => ['required', 'in:national,company,religious'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_recurring' => ['nullable', 'boolean'],
        ]);

        $holiday->update([
            'name' => $validated['name'],
            'date' => $validated['date'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_recurring' => $validated['is_recurring'] ?? false,
        ]);

        return redirect()->route('holidays.index')
            ->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        if ($holiday->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $holiday->delete();

        return redirect()->route('holidays.index')
            ->with('success', 'Hari libur berhasil dihapus.');
    }

    public function calendar(): View
    {
        return view('holidays.calendar');
    }

    public function events(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', now()->year);

        $holidays = Holiday::where('company_id', $companyId)
            ->whereYear('date', $year)
            ->active()
            ->orderBy('date')
            ->get()
            ->map(function ($holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'date' => $holiday->date->format('Y-m-d'),
                    'type' => $holiday->type,
                    'type_label' => $holiday->type_label,
                    'type_color' => $holiday->type_color,
                    'description' => $holiday->description,
                ];
            });

        return response()->json($holidays);
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $companyId = auth()->user()->company_id;
        $year = $validated['year'];

        // Indonesian national holidays (fixed dates)
        $nationalHolidays = [
            ['name' => 'Tahun Baru', 'date' => "{$year}-01-01", 'type' => 'national'],
            ['name' => 'Hari Buruh', 'date' => "{$year}-05-01", 'type' => 'national'],
            ['name' => 'Hari Pancasila', 'date' => "{$year}-06-01", 'type' => 'national'],
            ['name' => 'Hari Kemerdekaan', 'date' => "{$year}-08-17", 'type' => 'national'],
            ['name' => 'Hari Natal', 'date' => "{$year}-12-25", 'type' => 'religious'],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($nationalHolidays as $holiday) {
            $exists = Holiday::where('company_id', $companyId)
                ->whereDate('date', $holiday['date'])
                ->exists();

            if (! $exists) {
                Holiday::create([
                    'company_id' => $companyId,
                    'name' => $holiday['name'],
                    'date' => $holiday['date'],
                    'type' => $holiday['type'],
                    'is_recurring' => true,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        $message = "Berhasil menambahkan {$created} hari libur nasional.";
        if ($skipped > 0) {
            $message .= " {$skipped} tanggal dilewati karena sudah ada.";
        }

        return redirect()->route('holidays.index', ['year' => $year])
            ->with('success', $message);
    }
}
