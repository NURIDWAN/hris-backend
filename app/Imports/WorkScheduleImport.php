<?php

namespace App\Imports;

use App\Models\WorkSchedule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WorkScheduleImport implements SkipsEmptyRows, ToModel, WithHeadingRow, WithValidation
{
    protected int $companyId;

    protected int $successCount = 0;

    protected int $skipCount = 0;

    /** @var array<int, string> */
    protected array $errors = [];

    protected int $currentRow = 1;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function model(array $row): ?WorkSchedule
    {
        $this->currentRow++;

        // Check if code already exists
        $existingSchedule = WorkSchedule::where('company_id', $this->companyId)
            ->where('code', $row['kode'])
            ->first();

        if ($existingSchedule) {
            $this->skipCount++;
            $this->errors[] = "Baris {$this->currentRow}: Kode '{$row['kode']}' sudah ada, dilewati.";

            return null;
        }

        $this->successCount++;

        return new WorkSchedule([
            'company_id' => $this->companyId,
            'name' => $row['nama'],
            'code' => $row['kode'],
            'start_time' => $this->parseTime($row['jam_masuk']),
            'end_time' => $this->parseTime($row['jam_keluar']),
            'break_start' => ! empty($row['jam_istirahat_mulai']) ? $this->parseTime($row['jam_istirahat_mulai']) : null,
            'break_end' => ! empty($row['jam_istirahat_selesai']) ? $this->parseTime($row['jam_istirahat_selesai']) : null,
            'break_duration' => $row['durasi_istirahat'] ?? 60,
            'working_days' => $this->parseWorkingDays($row['hari_kerja'] ?? '1,2,3,4,5'),
            'late_tolerance' => $row['toleransi_terlambat'] ?? 15,
            'early_leave_tolerance' => $row['toleransi_pulang_awal'] ?? 0,
            'is_flexible' => $this->parseBoolean($row['fleksibel'] ?? 'Tidak'),
            'is_default' => $this->parseBoolean($row['default'] ?? 'Tidak'),
            'is_active' => $this->parseBoolean($row['aktif'] ?? 'Ya'),
            'description' => $row['deskripsi'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:50'],
            'jam_masuk' => ['required'],
            'jam_keluar' => ['required'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Kolom Nama wajib diisi.',
            'kode.required' => 'Kolom Kode wajib diisi.',
            'jam_masuk.required' => 'Kolom Jam Masuk wajib diisi.',
            'jam_keluar.required' => 'Kolom Jam Keluar wajib diisi.',
        ];
    }

    protected function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['ya', 'yes', '1', 'true', 'aktif', 'active']);
    }

    protected function parseTime(mixed $value): string
    {
        if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
            return $value->format('H:i');
        }

        // Handle Excel time serial numbers
        if (is_numeric($value) && $value < 1) {
            $hours = (int) ($value * 24);
            $minutes = (int) (($value * 24 - $hours) * 60);

            return sprintf('%02d:%02d', $hours, $minutes);
        }

        return (string) $value;
    }

    /**
     * Parse working days from comma-separated string or array
     *
     * @return array<int>
     */
    protected function parseWorkingDays(mixed $value): array
    {
        if (is_array($value)) {
            return array_map('intval', $value);
        }

        $value = str_replace(' ', '', (string) $value);

        return array_map('intval', explode(',', $value));
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getSkipCount(): int
    {
        return $this->skipCount;
    }

    /** @return array<int, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
