<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Position;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PositionImport implements SkipsEmptyRows, ToModel, WithHeadingRow, WithValidation
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

    public function model(array $row): ?Position
    {
        $this->currentRow++;

        // Check if code already exists
        $existingPosition = Position::where('company_id', $this->companyId)
            ->where('code', $row['kode'])
            ->first();

        if ($existingPosition) {
            $this->skipCount++;
            $this->errors[] = "Baris {$this->currentRow}: Kode '{$row['kode']}' sudah ada, dilewati.";

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
                $this->errors[] = "Baris {$this->currentRow}: Departemen '{$row['kode_departemen']}' tidak ditemukan.";
            }
        }

        $this->successCount++;

        return new Position([
            'company_id' => $this->companyId,
            'department_id' => $departmentId,
            'name' => $row['nama'],
            'code' => $row['kode'],
            'description' => $row['deskripsi'] ?? null,
            'level' => $row['level'] ?? 1,
            'base_salary' => $this->parseSalary($row['gaji_pokok'] ?? 0),
            'is_active' => $this->parseBoolean($row['aktif'] ?? 'Ya'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:50'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Kolom Nama wajib diisi.',
            'kode.required' => 'Kolom Kode wajib diisi.',
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
