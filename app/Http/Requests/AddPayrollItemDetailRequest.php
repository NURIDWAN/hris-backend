<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPayrollItemDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'component_name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
            'category' => ['required', Rule::in(['fixed', 'variable', 'benefit', 'overtime', 'loan', 'penalty', 'reimbursement', 'other'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_taxable' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'component_name.required' => 'Nama komponen wajib diisi.',
            'type.required' => 'Tipe komponen wajib dipilih.',
            'type.in' => 'Tipe komponen tidak valid.',
            'category.required' => 'Kategori komponen wajib dipilih.',
            'category.in' => 'Kategori komponen tidak valid.',
            'amount.required' => 'Jumlah wajib diisi.',
            'amount.numeric' => 'Jumlah harus berupa angka.',
            'amount.min' => 'Jumlah tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_taxable' => $this->boolean('is_taxable'),
        ]);
    }
}
