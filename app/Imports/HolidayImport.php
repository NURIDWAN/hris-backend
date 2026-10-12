<?php

namespace App\Imports;

use App\Models\Holiday;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class HolidayImport implements SkipsEmptyRows, ToModel, WithHeadingRow, WithValidation
{
    protected int $companyId;

    protected int $createdBy;

    protected int $successCount = 0;

    protected int $skipCount = 0;

    /** @var array<int, string> */
    protected array $errors = [];

    protected int $currentRow = 1;

    public function __construct(int $companyId, int $createdBy)
    {
        $this->companyId = $companyId;
        $this->createdBy = $createdBy;
    }

    public function model(array $row): ?Holiday
    {
        $this->currentRow++;

        $date = $this->parseDate($row['tanggal']);

        if (! $date) {
            $this->skipCount++;
            $this->errors[] = "Baris {$this->currentRow}: Format tanggal tidak valid.";

            return null;
        }

        // Check if date already exists for this company
        $existingHoliday = Holiday::where('company_id', $this->companyId)
            ->whereDate('date', $date)
            ->first();

        if ($existingHoliday) {
            $this->skipCount++;
            $this->errors[] = "Baris {$this->currentRow}: Tanggal '{$date}' sudah terdaftar, dilewati.";

            return null;
        }

        $this->successCount++;

        return new Holiday([
            'company_id' => $this->companyId,
            'name' => $row['nama'],
            'date' => $date,
            'type' => $this->parseType($row['jenis'] ?? 'national'),
            'description' => $row['deskripsi'] ?? null,
            'is_recurring' => $this->parseBoolean($row['berulang'] ?? 'Tidak'),
            'is_active' => $this->parseBoolean($row['aktif'] ?? 'Ya'),
            'created_by' => $this->createdBy,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'tanggal' => ['required'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Kolom Nama wajib diisi.',
            'tanggal.required' => 'Kolom Tanggal wajib diisi.',
        ];
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Handle Excel numeric date format
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Handle string date formats
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($value));
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    protected function parseType(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'nasional', 'national' => 'national',
            'perusahaan', 'company' => 'company',
            'keagamaan', 'religious' => 'religious',
            default => 'national',
        };
    }

    protected function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['ya', 'yes', '1', 'true']);
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
