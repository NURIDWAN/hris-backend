<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpgradeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => [
                'required',
                'string',
                Rule::in([Subscription::CYCLE_MONTHLY, Subscription::CYCLE_YEARLY]),
            ],
            'gateway' => ['required', 'string', Rule::in(['xendit', 'midtrans', 'manual'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'Pilih paket langganan.',
            'plan_id.exists' => 'Paket langganan tidak valid.',
            'billing_cycle.required' => 'Pilih siklus pembayaran.',
            'billing_cycle.in' => 'Siklus pembayaran harus monthly atau yearly.',
            'gateway.required' => 'Pilih gateway pembayaran.',
            'gateway.in' => 'Gateway pembayaran tidak valid.',
        ];
    }
}
