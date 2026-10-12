<?php

namespace App\Imports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DepartmentImport implements SkipsEmptyRows, ToModel, WithHeadingRow, WithValidation
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

    public function model(array $row): ?Department
    {
        $this->currentRow++;

        // Check if code already exists
        $existingDepartment = Department::where('company_id', $this->companyId)
            ->where('code', $row['kode'])
            ->first();

        if ($existingDepartment) {
            $this->skipCount++;
            $this->errors[] = "Baris {$this->currentRow}: Kode '{$row['kode']}' sudah ada, dilewati.";

            return null;
        }

        // Resolve parent department
        $parentId = null;
        if (! empty($row['kode_induk'])) {
            $parent = Department::where('company_id', $this->companyId)
                ->where('code', $row['kode_induk'])
                ->first();

            if ($parent) {
                $parentId = $parent->id;
            } else {
                $this->errors[] = "Baris {$this->currentRow}: Kode induk '{$row['kode_induk']}' tidak ditemukan.";
            }
        }

        $this->successCount++;

        return new Department([
            'company_id' => $this->companyId,
            'parent_id' => $parentId,
            'name' => $row['nama'],
            'code' => $row['kode'],
            'description' => $row['deskripsi'] ?? null,
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
