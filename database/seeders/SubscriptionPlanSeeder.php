<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Untuk startup & UMKM kecil yang baru memulai.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_employees' => 5,
                'max_users' => 2,
                'features' => [
                    'Maksimal 5 karyawan',
                    'Kehadiran GPS & Selfie',
                    'Cuti & Izin dasar',
                    'Laporan kehadiran dasar',
                    'Mobile app akses',
                    'Email support',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Untuk bisnis berkembang dengan kebutuhan payroll lengkap.',
                'price_monthly' => 15000, // per user per bulan
                'price_yearly' => 144000, // per user per tahun (hemat 20%)
                'max_employees' => 100,
                'max_users' => 10,
                'features' => [
                    'Hingga 100 karyawan',
                    'Semua fitur Starter',
                    'Payroll otomatis',
                    'PPh 21 & BPJS otomatis',
                    'Lembur & THR',
                    'Reimbursement',
                    'Multi approval workflow',
                    'Laporan lengkap & export',
                    'Slip gaji digital',
                    'Priority support',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solusi lengkap untuk perusahaan besar dengan kebutuhan custom.',
                'price_monthly' => 0, // Custom pricing
                'price_yearly' => 0, // Custom pricing
                'max_employees' => 0, // unlimited
                'max_users' => 0, // unlimited
                'features' => [
                    'Unlimited karyawan',
                    'Semua fitur Professional',
                    'Multi-company / Multi-branch',
                    'Custom salary components',
                    'Advanced analytics & BI',
                    'API access & integration',
                    'Custom branding',
                    'Dedicated account manager',
                    'SLA 99.9% uptime',
                    'On-premise option',
                    'Training & onboarding',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Subscription plans seeded successfully!');

        // Create subscription for demo company
        $demoCompany = Company::where('slug', 'pt-demo-gajipro')->first();
        $professionalPlan = SubscriptionPlan::where('slug', 'professional')->first();

        if ($demoCompany && $professionalPlan) {
            // Update company with subscription plan
            $demoCompany->update([
                'subscription_plan' => 'professional',
                'subscription_plan_id' => $professionalPlan->id,
                'subscription_ends_at' => now()->addYear(),
                'max_employees' => $professionalPlan->max_employees,
                'is_demo_mode' => false,
            ]);

            // Create subscription record
            Subscription::updateOrCreate(
                ['company_id' => $demoCompany->id],
                [
                    'subscription_plan_id' => $professionalPlan->id,
                    'status' => 'active',
                    'billing_cycle' => 'yearly',
                    'started_at' => now(),
                    'ends_at' => now()->addYear(),
                    'trial_ends_at' => null,
                ]
            );

            $this->command->info('Demo company subscription created!');
        }
    }
}
