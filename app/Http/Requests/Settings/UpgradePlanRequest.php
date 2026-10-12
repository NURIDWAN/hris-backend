<?php

namespace App\Http\Requests\Settings;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpgradePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'billing_cycle' => [
                'required',
                'string',
                Rule::in([Subscription::CYCLE_MONTHLY, Subscription::CYCLE_YEARLY]),
            ],
            'gateway' => ['required', 'string', Rule::in(['xendit', 'midtrans', 'manual'])],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Pilih paket langganan.',
            'plan_id.exists' => 'Paket langganan tidak valid.',
            'billing_cycle.required' => 'Pilih siklus pembayaran.',
            'billing_cycle.in' => 'Siklus pembayaran tidak valid.',
            'gateway.required' => 'Pilih metode pembayaran.',
            'gateway.in' => 'Metode pembayaran tidak valid.',
        ];
    }
}
