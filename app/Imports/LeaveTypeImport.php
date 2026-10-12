<?php

namespace App\Imports;

use App\Models\LeaveType;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class LeaveTypeImport implements SkipsEmptyRows, ToModel, WithHeadingRow, WithValidation
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

    public function model(array $row): ?LeaveType
    {
        $this->currentRow++;

        // Check if code already exists
        $existingLeaveType = LeaveType::where('company_id', $this->companyId)
            ->where('code', $row['kode'])
            ->first();

        if ($existingLeaveType) {
            $this->skipCount++;
            $this->errors[] = "Baris {$this->currentRow}: Kode '{$row['kode']}' sudah ada, dilewati.";

            return null;
        }

        $this->successCount++;

        return new LeaveType([
            'company_id' => $this->companyId,
            'name' => $row['nama'],
            'code' => $row['kode'],
            'description' => $row['deskripsi'] ?? null,
            'default_days' => $row['jatah_hari'] ?? 0,
            'is_paid' => $this->parseBoolean($row['berbayar'] ?? 'Ya'),
            'requires_approval' => $this->parseBoolean($row['perlu_persetujuan'] ?? 'Ya'),
            'requires_attachment' => $this->parseBoolean($row['perlu_lampiran'] ?? 'Tidak'),
            'max_consecutive_days' => $row['maksimal_hari_berturut'] ?? null,
            'min_notice_days' => $row['minimal_hari_pengajuan'] ?? 0,
            'is_carry_forward' => $this->parseBoolean($row['bisa_dibawa'] ?? 'Tidak'),
            'max_carry_forward_days' => $row['maksimal_hari_dibawa'] ?? null,
            'is_annual' => $this->parseBoolean($row['tahunan'] ?? 'Tidak'),
            'is_active' => $this->parseBoolean($row['aktif'] ?? 'Ya'),
            'color' => $row['warna'] ?? 'primary',
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
