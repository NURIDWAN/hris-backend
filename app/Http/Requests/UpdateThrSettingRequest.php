<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThrSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calculation_method' => ['required', 'string', 'in:one_month_salary,prorata'],
            'min_service_months' => ['required', 'integer', 'min:0', 'max:12'],
            'prorata_formula' => ['required', 'string', 'in:months_worked_per_12'],
            'include_allowances' => ['required', 'boolean'],
            'religious_holiday' => ['required', 'string', 'in:idul_fitri,christmas,nyepi,waisak,imlek'],
            'payment_days_before' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'calculation_method.required' => 'Metode perhitungan wajib dipilih.',
            'calculation_method.in' => 'Metode perhitungan tidak valid.',
            'min_service_months.required' => 'Minimal masa kerja wajib diisi.',
            'min_service_months.min' => 'Minimal masa kerja tidak boleh negatif.',
            'religious_holiday.required' => 'Hari raya wajib dipilih.',
            'religious_holiday.in' => 'Hari raya tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'include_allowances' => $this->boolean('include_allowances'),
        ]);
    }
}
