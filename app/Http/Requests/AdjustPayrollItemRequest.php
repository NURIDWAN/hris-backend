<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustPayrollItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'details' => ['sometimes', 'array'],
            'details.*.id' => ['required', 'integer', 'exists:payroll_item_details,id'],
            'details.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'basic_salary.required' => 'Gaji pokok wajib diisi.',
            'basic_salary.numeric' => 'Gaji pokok harus berupa angka.',
            'basic_salary.min' => 'Gaji pokok tidak boleh negatif.',
            'details.*.amount.required' => 'Jumlah komponen wajib diisi.',
            'details.*.amount.numeric' => 'Jumlah komponen harus berupa angka.',
            'details.*.amount.min' => 'Jumlah komponen tidak boleh negatif.',
        ];
    }
}
