@extends('layouts.admin')

@section('title', 'Upgrade Paket')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('settings.billing.index') }}" class="text-slate-500 hover:text-primary-600">Billing</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Upgrade</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pilih Paket Langganan</h1>
            <p class="text-secondary-500 mt-1">Pilih paket yang sesuai dengan kebutuhan perusahaan Anda.</p>
        </div>
        <a href="{{ route('settings.billing.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div x-data="{ billingCycle: 'monthly', selectedPlan: null }">
        {{-- Billing Cycle Toggle --}}
        <div class="flex justify-center mb-8">
            <div class="bg-secondary-100 rounded-lg p-1 inline-flex">
                <button type="button"
                        @click="billingCycle = 'monthly'"
                        :class="billingCycle === 'monthly' ? 'bg-white shadow' : ''"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                    Bulanan
                </button>
                <button type="button"
                        @click="billingCycle = 'yearly'"
                        :class="billingCycle === 'yearly' ? 'bg-white shadow' : ''"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                    Tahunan
                    <span class="text-success-600 text-xs ml-1">Hemat 17%</span>
                </button>
            </div>
        </div>

        {{-- Plans Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @foreach($plans as $plan)
                <div @click="selectedPlan = {{ $plan->id }}"
                     :class="selectedPlan === {{ $plan->id }} ? 'ring-2 ring-primary-500 border-primary-500' : 'border-secondary-200'"
                     class="border rounded-xl p-6 cursor-pointer transition-all hover:shadow-lg {{ $currentSubscription && $currentSubscription->subscription_plan_id == $plan->id ? 'bg-primary-50' : 'bg-white' }}">

                    @if($currentSubscription && $currentSubscription->subscription_plan_id == $plan->id)
                        <div class="text-xs font-medium text-primary-600 mb-2">PAKET SAAT INI</div>
                    @endif

                    <h3 class="text-xl font-bold text-secondary-900 mb-2">{{ $plan->name }}</h3>

                    <div class="mb-4">
                        <template x-if="billingCycle === 'monthly'">
                            <div>
                                <span class="text-3xl font-bold text-secondary-900">{{ $plan->formatted_price_monthly }}</span>
                                <span class="text-secondary-500">/bulan</span>
                            </div>
                        </template>
                        <template x-if="billingCycle === 'yearly'">
                            <div>
                                <span class="text-3xl font-bold text-secondary-900">{{ $plan->formatted_price_yearly }}</span>
                                <span class="text-secondary-500">/tahun</span>
                            </div>
                        </template>
                    </div>

                    @if($plan->description)
                        <p class="text-sm text-secondary-500 mb-4">{{ $plan->description }}</p>
                    @endif

                    <ul class="space-y-3 text-sm">
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-secondary-700">
                                @if($plan->hasUnlimitedEmployees())
                                    <strong>Unlimited</strong> karyawan
                                @else
                                    Hingga <strong>{{ $plan->max_employees }}</strong> karyawan
                                @endif
                            </span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-success-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-secondary-700">
                                @if($plan->hasUnlimitedUsers())
                                    <strong>Unlimited</strong> pengguna
                                @else
                                    Hingga <strong>{{ $plan->max_users }}</strong> pengguna
                                @endif
                            </span>
                        </li>
                        @if($plan->features)
                            @foreach($plan->features as $feature)
                                <li class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-success-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-secondary-700">{{ $feature }}</span>
                                </li>
                            @endforeach
                        @endif
                    </ul>

                    <div class="mt-6">
                        <div :class="selectedPlan === {{ $plan->id }} ? 'bg-primary-600 text-white' : 'bg-secondary-100 text-secondary-700'"
                             class="w-full py-2 px-4 rounded-lg text-center font-medium transition-all">
                            <span x-show="selectedPlan === {{ $plan->id }}">Terpilih</span>
                            <span x-show="selectedPlan !== {{ $plan->id }}">Pilih Paket</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Submit Form --}}
        <form action="{{ route('settings.billing.process-upgrade') }}" method="POST" x-show="selectedPlan" x-cloak>
            @csrf
            <input type="hidden" name="plan_id" :value="selectedPlan">
            <input type="hidden" name="billing_cycle" :value="billingCycle">

            <div class="flex justify-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Lanjutkan ke Pembayaran
                </button>
            </div>
        </form>

        @error('plan_id')
            <p class="mt-4 text-center text-sm text-danger-600">{{ $message }}</p>
        @enderror
        @error('billing_cycle')
            <p class="mt-4 text-center text-sm text-danger-600">{{ $message }}</p>
        @enderror
    </div>
@endsection
