<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ScheduleController extends Controller
{
    #[OA\Get(
        path: '/schedule',
        summary: 'Jadwal kerja bulanan karyawan',
        description: 'Mengembalikan jadwal kerja karyawan untuk satu bulan penuh, menggunakan resolveScheduleForDate() untuk mendukung weekly schedule pattern dan default schedule.',
        tags: ['Schedule'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'month',
                in: 'query',
                required: false,
                description: 'Bulan dalam format YYYY-MM. Default: bulan ini.',
                schema: new OA\Schema(type: 'string', example: '2026-04')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Jadwal berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data karyawan tidak ditemukan'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan',
            ], 404);
        }

        $company = $user->company;

        // Parse requested month or use current month
        $monthParam = $request->query('month');
        if ($monthParam && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $monthParam, $company->timezone)->startOfMonth();
        } else {
            $startOfMonth = $company->now()->startOfMonth();
        }

        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Eager load weekly schedules for performance
        $employee->load('weeklySchedules.workSchedule', 'workSchedule');

        // Get holidays for the month
        $holidays = Holiday::getHolidaysForDateRange(
            $company->id,
            $startOfMonth->toDateString(),
            $endOfMonth->toDateString()
        )->keyBy(fn (Holiday $h) => $h->date->format('Y-m-d'));

        // Build days array
        $days = [];
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $holiday = $holidays->get($dateString);
            $schedule = $employee->resolveScheduleForDate($date);

            $days[] = [
                'date' => $dateString,
                'day_name' => $this->indonesianDayName($date->dayOfWeekIso),
                'is_working_day' => $schedule !== null && ! $holiday,
                'is_holiday' => $holiday !== null,
                'holiday_name' => $holiday?->name,
                'schedule' => $schedule ? [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                    'is_overnight' => $schedule->is_overnight,
                ] : null,
            ];
        }

        // Month navigation
        $prevMonth = $startOfMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $startOfMonth->copy()->addMonth()->format('Y-m');

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $startOfMonth->format('Y-m'),
                'month_label' => $startOfMonth->translatedFormat('F Y'),
                'prev_month' => $prevMonth,
                'next_month' => $nextMonth,
                'days' => $days,
            ],
        ]);
    }

    private function indonesianDayName(int $dayOfWeekIso): string
    {
        return match ($dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => '-',
        };
    }
}
