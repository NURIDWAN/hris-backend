<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk konfigurasi Billing & Subscription PT Demo GajiPro
 *
 * Data yang dibuat:
 * =================
 *
 * 1. Subscription Plans (4 paket)
 *    - Free: Gratis untuk 5 karyawan
 *    - Starter: Rp 500K/bulan untuk 25 karyawan
 *    - Professional: Rp 1.5jt/bulan untuk 100 karyawan
 *    - Enterprise: Rp 3.5jt/bulan untuk unlimited karyawan
 *
 * 2. Subscription untuk Demo Company
 *    - Plan: Professional
 *    - Billing Cycle: Yearly (lebih hemat)
 *    - Status: Active
 *
 * 3. Invoice History (6 bulan terakhir)
 *    - Invoice pembayaran tahunan
 *    - Status: Paid
 *
 * 4. Payment History
 *    - Riwayat pembayaran via Bank Transfer
 */
class DemoBillingSeeder extends Seeder
{
    private Company $company;

    private array $plans = [];

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Membuat Billing Data untuk: {$this->company->name}");

        // Hapus data lama
        $this->cleanExistingData();

        // Buat data
        $this->createSubscriptionPlans();
        $this->createSubscription();
        $this->createInvoicesAndPayments();

        $this->printSummary();
    }

    private function cleanExistingData(): void
    {
        $this->command->info('Menghapus data billing lama...');

        // Hapus payments, invoices, subscriptions untuk company demo
        Payment::where('company_id', $this->company->id)->delete();
        Invoice::where('company_id', $this->company->id)->delete();
        Subscription::where('company_id', $this->company->id)->delete();

        // Hapus semua subscription plans untuk di-recreate
        SubscriptionPlan::query()->delete();
    }

    private function createSubscriptionPlans(): void
    {
        $this->command->info('Membuat Subscription Plans...');

        // Plan 1: Free
        $this->plans['free'] = SubscriptionPlan::create([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Paket gratis untuk UMKM dan startup yang baru memulai. Cocok untuk tim kecil dengan kebutuhan dasar.',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_employees' => 5,
            'max_users' => 2,
            'features' => [
                'Manajemen Karyawan (max 5)',
                'Absensi & Kehadiran',
                'Penggajian Dasar',
                'Laporan Sederhana',
                'Email Support',
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Plan 2: Starter
        $this->plans['starter'] = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Paket untuk bisnis berkembang dengan tim yang lebih besar. Fitur lengkap untuk manajemen HR.',
            'price_monthly' => 500000,
            'price_yearly' => 5000000, // 2 bulan gratis
            'max_employees' => 25,
            'max_users' => 5,
            'features' => [
                'Semua fitur Free',
                'Manajemen Karyawan (max 25)',
                'Manajemen Cuti & Izin',
                'Lembur & Shift',
                'BPJS & PPh 21 Otomatis',
                'Multi Lokasi Kantor (max 3)',
                'Laporan Lengkap',
                'Priority Email Support',
            ],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Plan 3: Professional
        $this->plans['professional'] = SubscriptionPlan::create([
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Paket untuk perusahaan menengah dengan kebutuhan HR yang kompleks. Termasuk fitur premium.',
            'price_monthly' => 1500000,
            'price_yearly' => 15000000, // 2 bulan gratis
            'max_employees' => 100,
            'max_users' => 15,
            'features' => [
                'Semua fitur Starter',
                'Manajemen Karyawan (max 100)',
                'Approval Workflow',
                'Reimbursement',
                'Performance Review',
                'Training & Development',
                'Multi Lokasi Kantor (max 10)',
                'API Access',
                'Dedicated Account Manager',
                'Phone & Chat Support',
            ],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Plan 4: Enterprise
        $this->plans['enterprise'] = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Paket untuk perusahaan besar dengan kebutuhan kustomisasi tinggi. Unlimited employees dan fitur eksklusif.',
            'price_monthly' => 3500000,
            'price_yearly' => 35000000, // 2 bulan gratis
            'max_employees' => 0, // Unlimited
            'max_users' => 0, // Unlimited
            'features' => [
                'Semua fitur Professional',
                'Unlimited Karyawan',
                'Unlimited Users',
                'Unlimited Lokasi Kantor',
                'Custom Approval Workflow',
                'Advanced Analytics & BI',
                'Custom Reports',
                'SSO Integration',
                'Dedicated Server Option',
                'Custom Development',
                'SLA 99.9% Uptime',
                '24/7 Priority Support',
                'On-site Training',
            ],
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $this->command->info('  - 4 subscription plans dibuat');
    }

    private function createSubscription(): void
    {
        $this->command->info('Membuat Subscription untuk Demo Company...');

        $professionalPlan = $this->plans['professional'];

        // Subscription aktif dengan plan Professional, billing tahunan
        // Dimulai 6 bulan lalu, berakhir 6 bulan lagi
        $startedAt = Carbon::now()->subMonths(6)->startOfMonth();
        $endsAt = $startedAt->copy()->addYear();

        Subscription::create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $professionalPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_YEARLY,
            'started_at' => $startedAt,
            'ends_at' => $endsAt,
            'cancelled_at' => null,
            'trial_ends_at' => null,
        ]);

        // Update company dengan subscription plan
        $this->company->update([
            'subscription_plan_id' => $professionalPlan->id,
        ]);

        $this->command->info("  - Plan: {$professionalPlan->name}");
        $this->command->info('  - Billing: Tahunan');
        $this->command->info("  - Periode: {$startedAt->format('d M Y')} - {$endsAt->format('d M Y')}");
    }

    private function createInvoicesAndPayments(): void
    {
        $this->command->info('Membuat Invoice dan Payment History...');

        $subscription = Subscription::where('company_id', $this->company->id)->first();
        $plan = $subscription->plan;

        // Invoice untuk pembayaran tahunan (6 bulan lalu)
        $invoice = Invoice::create([
            'invoice_number' => 'INV'.date('Y').'080001',
            'company_id' => $this->company->id,
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_PAID,
            'subtotal' => $plan->price_yearly,
            'tax' => $plan->price_yearly * 0.11, // PPN 11%
            'discount' => 0,
            'total' => $plan->price_yearly * 1.11,
            'currency' => 'IDR',
            'description' => "Langganan {$plan->name} - Periode Tahunan ({$subscription->started_at->format('M Y')} - {$subscription->ends_at->format('M Y')})",
            'issued_at' => $subscription->started_at,
            'due_at' => $subscription->started_at->copy()->addDays(7),
            'paid_at' => $subscription->started_at->copy()->addDays(2),
            'payment_method' => 'bank_transfer',
            'payment_details' => [
                'bank' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'PT GajiPro Indonesia',
            ],
        ]);

        // Payment record
        Payment::create([
            'company_id' => $this->company->id,
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY'.date('Y').'08020001',
            'gateway' => 'manual',
            'gateway_transaction_id' => 'TRX-'.strtoupper(substr(md5(rand()), 0, 10)),
            'gateway_order_id' => 'ORD-'.strtoupper(substr(md5(rand()), 0, 10)),
            'amount' => $invoice->total,
            'fee' => 0,
            'net_amount' => $invoice->total,
            'currency' => 'IDR',
            'status' => Payment::STATUS_SUCCESS,
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'payment_channel' => 'BCA Virtual Account',
            'gateway_response' => [
                'bank' => 'BCA',
                'va_number' => '80777081234567890',
                'transaction_time' => $invoice->paid_at->toIso8601String(),
                'settlement_time' => $invoice->paid_at->copy()->addHours(2)->toIso8601String(),
            ],
            'description' => "Pembayaran {$invoice->invoice_number}",
            'billing_cycle' => Subscription::CYCLE_YEARLY,
            'paid_at' => $invoice->paid_at,
            'expired_at' => null,
        ]);

        $this->command->info("  - Invoice: {$invoice->invoice_number}");
        $this->command->info('  - Total: Rp '.number_format($invoice->total, 0, ',', '.'));
        $this->command->info('  - Status: Lunas');
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== BILLING DATA PT DEMO GAJIPRO ===');
        $this->command->newLine();

        // Subscription Plans
        $this->command->info('SUBSCRIPTION PLANS:');
        $this->command->line(str_repeat('-', 80));

        $plans = SubscriptionPlan::orderBy('sort_order')->get();
        foreach ($plans as $plan) {
            $employees = $plan->hasUnlimitedEmployees() ? 'Unlimited' : $plan->max_employees;
            $users = $plan->hasUnlimitedUsers() ? 'Unlimited' : $plan->max_users;
            $status = $plan->is_active ? '[AKTIF]' : '[NONAKTIF]';

            $this->command->info("{$plan->name} {$status}");
            $this->command->line("  Harga Bulanan: {$plan->formatted_price_monthly}");
            $this->command->line("  Harga Tahunan: {$plan->formatted_price_yearly}");
            $this->command->line("  Max Karyawan: {$employees}");
            $this->command->line("  Max Users: {$users}");

            if ($plan->features) {
                $this->command->line('  Fitur:');
                foreach ($plan->features as $feature) {
                    $this->command->line("    - {$feature}");
                }
            }
            $this->command->newLine();
        }

        // Current Subscription
        $subscription = Subscription::where('company_id', $this->company->id)
            ->with('plan')
            ->first();

        $this->command->info('LANGGANAN AKTIF:');
        $this->command->line(str_repeat('-', 80));
        $this->command->line("Plan: {$subscription->plan->name}");
        $this->command->line("Status: {$subscription->status_label}");
        $this->command->line("Billing: {$subscription->cycle_label}");
        $this->command->line("Mulai: {$subscription->started_at->format('d M Y')}");
        $this->command->line("Berakhir: {$subscription->ends_at->format('d M Y')}");
        $this->command->line("Sisa: {$subscription->daysUntilExpiry()} hari");
        $this->command->newLine();

        // Invoices
        $invoices = Invoice::where('company_id', $this->company->id)->get();
        $this->command->info('RIWAYAT INVOICE:');
        $this->command->line(str_repeat('-', 80));

        foreach ($invoices as $inv) {
            $this->command->line("{$inv->invoice_number} | {$inv->formatted_total} | {$inv->status_label} | {$inv->issued_at->format('d M Y')}");
        }
        $this->command->newLine();

        // Payments
        $payments = Payment::where('company_id', $this->company->id)->get();
        $this->command->info('RIWAYAT PEMBAYARAN:');
        $this->command->line(str_repeat('-', 80));

        foreach ($payments as $pay) {
            $this->command->line("{$pay->payment_number} | {$pay->formatted_amount} | {$pay->status_label} | {$pay->method_label}");
        }
        $this->command->newLine();

        // Summary
        $this->command->info('=== RINGKASAN ===');
        $this->command->line('Total Plans: '.$plans->count());
        $this->command->line('Total Invoices: '.$invoices->count());
        $this->command->line('Total Payments: '.$payments->count());
        $this->command->line('Total Revenue: Rp '.number_format($payments->where('status', Payment::STATUS_SUCCESS)->sum('amount'), 0, ',', '.'));
        $this->command->newLine();

        $this->command->info('Billing data berhasil dibuat!');
    }
}
