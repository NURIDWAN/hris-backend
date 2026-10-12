<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function index(): View
    {
        $gateways = PaymentGatewaySetting::orderBy('sort_order')->paginate(15);

        return view('superadmin.payment-gateways.index', compact('gateways'));
    }

    public function create(): View
    {
        return view('superadmin.payment-gateways.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gateway' => ['required', 'string', 'max:50', 'unique:payment_gateway_settings'],
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['nullable', 'in:sandbox,production'],
            'credentials' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['environment'] = $validated['environment'] ?? 'sandbox';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        PaymentGatewaySetting::create($validated);

        return redirect()->route('superadmin.payment-gateways.index')
            ->with('success', 'Payment gateway berhasil ditambahkan.');
    }

    public function edit(PaymentGatewaySetting $paymentGateway): View
    {
        return view('superadmin.payment-gateways.edit', compact('paymentGateway'));
    }

    public function update(Request $request, PaymentGatewaySetting $paymentGateway): RedirectResponse
    {
        $validated = $request->validate([
            'gateway' => ['required', 'string', 'max:50', 'unique:payment_gateway_settings,gateway,'.$paymentGateway->id],
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['nullable', 'in:sandbox,production'],
            'credentials' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $paymentGateway->update($validated);

        return redirect()->route('superadmin.payment-gateways.index')
            ->with('success', 'Payment gateway berhasil diupdate.');
    }

    public function destroy(PaymentGatewaySetting $paymentGateway): RedirectResponse
    {
        $paymentGateway->delete();

        return redirect()->route('superadmin.payment-gateways.index')
            ->with('success', 'Payment gateway berhasil dihapus.');
    }

    public function toggleStatus(PaymentGatewaySetting $paymentGateway): RedirectResponse
    {
        $paymentGateway->update(['is_active' => ! $paymentGateway->is_active]);

        return back()->with('success', 'Status payment gateway berhasil diubah.');
    }
}
