<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxForm1721A1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'tax_year' => ['required', 'integer', 'min:2020', 'max:'.(now()->year + 1)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan harus dipilih.',
            'employee_id.exists' => 'Karyawan tidak ditemukan.',
            'tax_year.required' => 'Tahun pajak harus diisi.',
            'tax_year.integer' => 'Tahun pajak harus berupa angka.',
            'tax_year.min' => 'Tahun pajak minimal 2020.',
            'tax_year.max' => 'Tahun pajak tidak valid.',
        ];
    }
}
