<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = app('tenant');
        $scheduleId = $this->route('work_schedule')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('work_schedules', 'code')
                    ->where('company_id', $tenant->id)
                    ->ignore($scheduleId),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i'],
            'break_duration' => ['nullable', 'integer', 'min:0', 'max:180'],
            'working_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['integer', 'min:1', 'max:7'],
            'late_tolerance' => ['nullable', 'integer', 'min:0', 'max:120'],
            'early_leave_tolerance' => ['nullable', 'integer', 'min:0', 'max:120'],
            'is_flexible' => ['boolean'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jadwal kerja wajib diisi.',
            'name.max' => 'Nama jadwal kerja maksimal 255 karakter.',
            'code.unique' => 'Kode jadwal kerja sudah digunakan.',
            'code.max' => 'Kode jadwal kerja maksimal 50 karakter.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'start_time.date_format' => 'Format jam mulai tidak valid (HH:MM).',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'end_time.date_format' => 'Format jam selesai tidak valid (HH:MM).',
            'break_start.date_format' => 'Format jam mulai istirahat tidak valid (HH:MM).',
            'break_end.date_format' => 'Format jam selesai istirahat tidak valid (HH:MM).',
            'break_duration.max' => 'Durasi istirahat maksimal 180 menit.',
            'working_hours.max' => 'Jam kerja maksimal 24 jam.',
            'late_tolerance.max' => 'Toleransi keterlambatan maksimal 120 menit.',
            'early_leave_tolerance.max' => 'Toleransi pulang awal maksimal 120 menit.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_flexible' => $this->boolean('is_flexible'),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
            'working_days' => $this->working_days ?? [1, 2, 3, 4, 5],
            'break_duration' => $this->break_duration ?? 60,
            'working_hours' => $this->working_hours ?? 8,
            'late_tolerance' => $this->late_tolerance ?? 15,
            'early_leave_tolerance' => $this->early_leave_tolerance ?? 15,
        ]);
    }
}
