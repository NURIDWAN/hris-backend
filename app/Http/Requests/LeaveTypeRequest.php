<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveTypeId = $this->route('leave_type')?->id;
        $companyId = auth()->user()->company_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:10',
                Rule::unique('leave_types')
                    ->where('company_id', $companyId)
                    ->ignore($leaveTypeId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_paid' => ['required', 'boolean'],
            'requires_approval' => ['required', 'boolean'],
            'requires_attachment' => ['required', 'boolean'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'min_notice_days' => ['required', 'integer', 'min:0', 'max:90'],
            'is_carry_forward' => ['required', 'boolean'],
            'max_carry_forward_days' => [
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('is_carry_forward')),
                'integer',
                'min:1',
                'max:365',
            ],
            'is_active' => ['required', 'boolean'],
            'color' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis cuti wajib diisi.',
            'code.unique' => 'Kode jenis cuti sudah digunakan.',
            'default_days.required' => 'Jumlah hari default wajib diisi.',
            'default_days.min' => 'Jumlah hari default tidak boleh negatif.',
            'max_carry_forward_days.required' => 'Maksimal hari carry forward wajib diisi jika carry forward aktif.',
        ];
    }
}
