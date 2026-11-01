<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\TenantRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected TenantRegistrationService $registrationService
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan' => ['nullable', 'string'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
            'gateway' => ['nullable', 'string'],
        ]);

        $result = $this->registrationService->registerTenant($validated);

        event(new Registered($result['user']));
        Auth::login($result['user']);

        // Handle paid plan - redirect to payment gateway
        if ($result['isPaidPlan'] && $result['payment']) {
            $payment = $result['payment'];

            // Handle manual payment
            if ($result['gateway'] === 'manual') {
                return redirect()->route('settings.billing.invoice', $payment)
                    ->with('success', 'Akun berhasil dibuat! Silakan transfer ke rekening yang tertera dan konfirmasi pembayaran.');
            }

            // Handle online payment gateway
            $gatewayResult = $result['gatewayResult'] ?? null;
            $redirectUrl = $gatewayResult['invoice_url'] ?? $gatewayResult['redirect_url'] ?? null;
            if ($redirectUrl) {
                return redirect($redirectUrl);
            }

            if ($payment->status === Payment::STATUS_FAILED) {
                return redirect()->route('dashboard')
                    ->with('warning', 'Akun berhasil dibuat! Namun pembayaran gagal diproses. Silakan coba lagi di menu Billing.');
            }
        }

        return redirect()->intended('/dashboard');
    }
}
