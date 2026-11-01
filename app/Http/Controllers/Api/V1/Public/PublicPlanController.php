<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class PublicPlanController extends Controller
{
    /**
     * Get active subscription plans for landing and pricing pages.
     */
    public function index(): JsonResponse
    {
        try {
            $plans = SubscriptionPlan::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            if ($plans->isEmpty()) {
                $plans = collect($this->defaultPlans());
            }
        } catch (\Throwable $e) {
            $plans = collect($this->defaultPlans());
        }

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Fallback default plans if database is not seeded.
     */
    protected function defaultPlans(): array
    {
        return [
            [
                'id' => 1,
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
                'id' => 2,
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Untuk bisnis berkembang dengan kebutuhan payroll lengkap.',
                'price_monthly' => 15000,
                'price_yearly' => 144000,
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
                'id' => 3,
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solusi lengkap untuk perusahaan besar dengan kebutuhan custom.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_employees' => 0,
                'max_users' => 0,
                'features' => [
                    'Unlimited karyawan',
                    'Semua fitur Professional',
                    'Multi-company / Multi-branch',
                    'Custom salary components',
                    'Advanced analytics & BI',
                    'Dedicated account manager',
                    'Custom integration & SLA',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];
    }
}
