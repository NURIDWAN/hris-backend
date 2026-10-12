<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;

class EmployeeImport implements ShouldQueue, SkipsEmptyRows, ToModel, WithChunkReading, WithEvents, WithHeadingRow, WithValidation
{
    protected int $companyId;

    protected string $importId;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct(int $companyId, ?string $importId = null)
    {
        $this->companyId = $companyId;
        $this->importId = $importId ?? uniqid('emp_import_');
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $this->markAsCompleted();
            },
            ImportFailed::class => function (ImportFailed $event) {
                $this->markAsFailed($event->getException()->getMessage());
            },
        ];
    }

    public function getImportId(): string
    {
        return $this->importId;
    }

    protected function getCacheKey(): string
    {
        return "employee_import_{$this->importId}";
    }

    protected function incrementCounter(string $key): void
    {
        $cacheKey = $this->getCacheKey();
        $data = Cache::get($cacheKey, $this->getDefaultCacheData());
        $data[$key]++;
        Cache::put($cacheKey, $data, now()->addHours(24));
    }

    protected function addError(string $error): void
    {
        $cacheKey = $this->getCacheKey();
        $data = Cache::get($cacheKey, $this->getDefaultCacheData());
        $data['errors'][] = $error;
        Cache::put($cacheKey, $data, now()->addHours(24));
    }

    protected function getDefaultCacheData(): array
    {
        return [
            'status' => 'processing',
            'success_count' => 0,
            'skip_count' => 0,
            'errors' => [],
            'started_at' => now()->toDateTimeString(),
            'completed_at' => null,
        ];
    }

    public function initializeImport(): void
    {
        Cache::put($this->getCacheKey(), $this->getDefaultCacheData(), now()->addHours(24));
    }

    protected function markAsCompleted(): void
    {
        $cacheKey = $this->getCacheKey();
        $data = Cache::get($cacheKey, $this->getDefaultCacheData());
        $data['status'] = 'completed';
        $data['completed_at'] = now()->toDateTimeString();
        Cache::put($cacheKey, $data, now()->addHours(24));
    }

    protected function markAsFailed(string $message): void
    {
        $cacheKey = $this->getCacheKey();
        $data = Cache::get($cacheKey, $this->getDefaultCacheData());
        $data['status'] = 'failed';
        $data['error_message'] = $message;
        $data['completed_at'] = now()->toDateTimeString();
        Cache::put($cacheKey, $data, now()->addHours(24));
    }

    public static function getImportStatus(string $importId): ?array
    {
        return Cache::get("employee_import_{$importId}");
    }

    public function model(array $row): ?Employee
    {
        // Check if employee_id already exists
        $existingEmployee = Employee::where('company_id', $this->companyId)
            ->where('employee_id', $row['nik'])
            ->first();

        if ($existingEmployee) {
            $this->incrementCounter('skip_count');
            $this->addError("NIK '{$row['nik']}' sudah ada, dilewati.");

            return null;
        }

        // Resolve department
        $departmentId = null;
        if (! empty($row['kode_departemen'])) {
            $department = Department::where('company_id', $this->companyId)
                ->where('code', $row['kode_departemen'])
                ->first();

            if ($department) {
                $departmentId = $department->id;
            } else {
                $this->addError("Departemen '{$row['kode_departemen']}' tidak ditemukan.");
            }
        }

        // Resolve position
        $positionId = null;
        if (! empty($row['kode_jabatan'])) {
            $position = Position::where('company_id', $this->companyId)
                ->where('code', $row['kode_jabatan'])
                ->first();

            if ($position) {
                $positionId = $position->id;
            } else {
                $this->addError("Jabatan '{$row['kode_jabatan']}' tidak ditemukan.");
            }
        }

        // Resolve work schedule
        $workScheduleId = null;
        if (! empty($row['kode_jadwal'])) {
            $workSchedule = WorkSchedule::where('company_id', $this->companyId)
                ->where('code', $row['kode_jadwal'])
                ->first();

            if ($workSchedule) {
                $workScheduleId = $workSchedule->id;
            } else {
                $this->addError("Jadwal kerja '{$row['kode_jadwal']}' tidak ditemukan.");
            }
        }

        $this->incrementCounter('success_count');

        return new Employee([
            'company_id' => $this->companyId,
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'work_schedule_id' => $workScheduleId,
            'employee_id' => $row['nik'],
            'first_name' => $row['nama_depan'],
            'last_name' => $row['nama_belakang'] ?? '',
            'email' => $row['email'] ?? null,
            'phone' => $row['telepon'] ?? null,
            'date_of_birth' => $this->parseDate($row['tanggal_lahir'] ?? null),
            'gender' => $this->parseGender($row['jenis_kelamin'] ?? null),
            'marital_status' => $this->parseMaritalStatus($row['status_pernikahan'] ?? null),
            'religion' => $row['agama'] ?? null,
            'blood_type' => $row['golongan_darah'] ?? null,
            'identity_number' => $row['no_ktp'] ?? null,
            'identity_address' => $row['alamat_ktp'] ?? null,
            'address' => $row['alamat'] ?? null,
            'city' => $row['kota'] ?? null,
            'province' => $row['provinsi'] ?? null,
            'postal_code' => $row['kode_pos'] ?? null,
            'hire_date' => $this->parseDate($row['tanggal_masuk'] ?? null),
            'employment_status' => $this->parseEmploymentStatus($row['status_karyawan'] ?? 'permanent'),
            'contract_start_date' => $this->parseDate($row['tanggal_mulai_kontrak'] ?? null),
            'contract_end_date' => $this->parseDate($row['tanggal_selesai_kontrak'] ?? null),
            'base_salary' => $this->parseSalary($row['gaji_pokok'] ?? 0),
            'bank_name' => $row['nama_bank'] ?? null,
            'bank_account_number' => $row['nomor_rekening'] ?? null,
            'bank_account_name' => $row['nama_rekening'] ?? null,
            'npwp' => $row['npwp'] ?? null,
            'tax_status' => $row['status_pajak'] ?? null,
            'bpjs_kesehatan' => $row['bpjs_kesehatan'] ?? null,
            'bpjs_ketenagakerjaan' => $row['bpjs_ketenagakerjaan'] ?? null,
            'emergency_contact_name' => $row['nama_kontak_darurat'] ?? null,
            'emergency_contact_phone' => $row['telepon_kontak_darurat'] ?? null,
            'emergency_contact_relationship' => $row['hubungan_kontak_darurat'] ?? null,
            'is_active' => $this->parseBoolean($row['aktif'] ?? 'Ya'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:50'],
            'nama_depan' => ['required', 'string', 'max:255'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nik.required' => 'Kolom NIK wajib diisi.',
            'nama_depan.required' => 'Kolom Nama Depan wajib diisi.',
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

    protected function parseSalary(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        // Remove Indonesian/common number formatting
        $value = str_replace(['.', ',', 'Rp', 'Rp.', ' '], '', (string) $value);

        return (int) $value;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTime || $value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        // Handle Excel serial date
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp(($value - 25569) * 86400)->format('Y-m-d');
        }

        // Try common date formats
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        // Last resort: try Carbon's parse
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseGender(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        if (in_array($value, ['male', 'laki-laki', 'laki', 'l', 'pria', 'm'])) {
            return 'male';
        }

        if (in_array($value, ['female', 'perempuan', 'wanita', 'p', 'f'])) {
            return 'female';
        }

        return null;
    }

    protected function parseMaritalStatus(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        $mapping = [
            'single' => 'single',
            'belum menikah' => 'single',
            'lajang' => 'single',
            'tk' => 'single',
            'married' => 'married',
            'menikah' => 'married',
            'kawin' => 'married',
            'k' => 'married',
            'divorced' => 'divorced',
            'cerai' => 'divorced',
            'cerai hidup' => 'divorced',
            'widowed' => 'widowed',
            'janda' => 'widowed',
            'duda' => 'widowed',
            'cerai mati' => 'widowed',
        ];

        return $mapping[$value] ?? null;
    }

    protected function parseEmploymentStatus(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        $mapping = [
            'permanent' => 'permanent',
            'tetap' => 'permanent',
            'pkwtt' => 'permanent',
            'contract' => 'contract',
            'kontrak' => 'contract',
            'pkwt' => 'contract',
            'probation' => 'probation',
            'percobaan' => 'probation',
            'masa percobaan' => 'probation',
            'intern' => 'intern',
            'magang' => 'intern',
        ];

        return $mapping[$value] ?? 'permanent';
    }
}
