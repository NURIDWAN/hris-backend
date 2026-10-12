<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'timezone' => ['nullable', 'string', 'timezone'],
            'date_format' => ['nullable', 'string', 'in:d/m/Y,m/d/Y,Y-m-d,d-m-Y,d M Y'],
            'currency' => ['nullable', 'string', 'in:IDR,USD'],
            'working_days_per_month' => ['nullable', 'integer', 'min:1', 'max:31'],
        ];
    }

    public function messages(): array
    {
        return [
            'timezone.timezone' => 'Timezone tidak valid.',
            'date_format.in' => 'Format tanggal tidak valid.',
            'currency.in' => 'Mata uang tidak valid.',
            'working_days_per_month.min' => 'Hari kerja per bulan minimal 1.',
            'working_days_per_month.max' => 'Hari kerja per bulan maksimal 31.',
        ];
    }
}
