<?php

namespace App\Http\Requests;

use App\Models\LeaveBalance;
use Illuminate\Foundation\Http\FormRequest;

class LeaveRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable', 'boolean'],
            'half_day_type' => ['required_if:is_half_day,true', 'nullable', 'in:morning,afternoon'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'leave_type_id.required' => 'Jenis cuti wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'half_day_type.required_if' => 'Tipe setengah hari wajib dipilih.',
            'reason.required' => 'Alasan cuti wajib diisi.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $this->validateLeaveBalance($validator);
        });
    }

    protected function validateLeaveBalance($validator)
    {
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');
        $isHalfDay = $this->boolean('is_half_day');

        if ($isHalfDay) {
            $totalDays = 0.5;
        } else {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $totalDays = $start->diff($end)->days + 1;
        }

        $year = (new \DateTime($startDate))->format('Y');

        $balance = LeaveBalance::where('employee_id', $this->input('employee_id'))
            ->where('leave_type_id', $this->input('leave_type_id'))
            ->where('year', $year)
            ->first();

        if (!$balance) {
            $validator->errors()->add('leave_type_id', 'Tidak ada saldo cuti untuk jenis cuti ini di tahun ' . $year);
            return;
        }

        if (!$balance->hasEnoughBalance($totalDays)) {
            $validator->errors()->add('leave_type_id', 'Saldo cuti tidak mencukupi. Tersedia: ' . $balance->remaining_days . ' hari');
        }
    }
}
