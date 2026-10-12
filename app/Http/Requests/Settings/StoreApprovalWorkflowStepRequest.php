<?php

namespace App\Http\Requests\Settings;

use App\Models\ApprovalWorkflowStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalWorkflowStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approver_type' => [
                'required',
                'string',
                Rule::in(array_keys(ApprovalWorkflowStep::APPROVER_TYPES)),
            ],
            'approver_role' => [
                'nullable',
                'string',
                'required_if:approver_type,role',
                'exists:roles,name',
            ],
            'approver_user_id' => [
                'nullable',
                'integer',
                'required_if:approver_type,specific_user',
                'exists:users,id',
            ],
            'can_skip' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'approver_type.required' => 'Tipe approver wajib dipilih.',
            'approver_type.in' => 'Tipe approver tidak valid.',
            'approver_role.required_if' => 'Role approver wajib dipilih.',
            'approver_role.exists' => 'Role tidak ditemukan.',
            'approver_user_id.required_if' => 'Pengguna approver wajib dipilih.',
            'approver_user_id.exists' => 'Pengguna tidak ditemukan.',
        ];
    }
}
