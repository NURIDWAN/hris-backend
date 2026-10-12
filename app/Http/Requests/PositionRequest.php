<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = app('tenant');
        $positionId = $this->route('position')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('positions', 'code')
                    ->where('company_id', $tenant->id)
                    ->ignore($positionId),
            ],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')
                    ->where('company_id', $tenant->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['nullable', 'integer', 'min:1', 'max:20'],
            'base_salary' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jabatan wajib diisi.',
            'name.max' => 'Nama jabatan maksimal 255 karakter.',
            'code.unique' => 'Kode jabatan sudah digunakan.',
            'code.max' => 'Kode jabatan maksimal 50 karakter.',
            'department_id.required' => 'Departemen wajib dipilih.',
            'department_id.exists' => 'Departemen tidak valid.',
            'level.integer' => 'Level harus berupa angka.',
            'level.min' => 'Level minimal 1.',
            'level.max' => 'Level maksimal 20.',
            'base_salary.integer' => 'Gaji pokok harus berupa angka.',
            'base_salary.min' => 'Gaji pokok tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'base_salary' => $this->base_salary ? (int) str_replace(['.', ','], '', $this->base_salary) : 0,
        ]);
    }
}
