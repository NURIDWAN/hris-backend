<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = app('tenant');

        return [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $tenant->id),
            ],
            'date' => ['required', 'date'],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', Rule::in(['present', 'absent', 'late', 'half_day', 'leave', 'holiday', 'weekend'])],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'employee_id.exists' => 'Karyawan tidak valid.',
            'date.required' => 'Tanggal wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'clock_in.date_format' => 'Format jam masuk tidak valid (HH:MM).',
            'clock_out.date_format' => 'Format jam pulang tidak valid (HH:MM).',
            'status.in' => 'Status tidak valid.',
        ];
    }
}
