<?php

namespace App\Http\Requests\Settings;

use App\Models\ApprovalWorkflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app('tenant')->id;

        return [
            'type' => [
                'required',
                'string',
                Rule::in(array_keys(ApprovalWorkflow::TYPES)),
                Rule::unique('approval_workflows')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Tipe alur persetujuan wajib dipilih.',
            'type.in' => 'Tipe alur persetujuan tidak valid.',
            'type.unique' => 'Alur persetujuan untuk tipe ini sudah ada.',
            'name.required' => 'Nama alur persetujuan wajib diisi.',
        ];
    }
}
