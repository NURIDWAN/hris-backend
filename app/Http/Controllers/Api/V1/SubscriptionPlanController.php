<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class SubscriptionPlanController extends Controller
{
    /**
     * Mengambil daftar paket langganan yang aktif.
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar paket langganan berhasil diambil.',
            'data' => $plans,
        ]);
    }

    /**
     * Mengambil detail satu paket langganan berdasarkan slug atau ID.
     */
    public function show(string $slug): JsonResponse
    {
        $plan = SubscriptionPlan::where('is_active', true)
            ->where(function ($query) use ($slug) {
                if (is_numeric($slug)) {
                    $query->where('id', (int) $slug);
                } else {
                    $query->where('slug', $slug);
                }
            })
            ->first();

        if (! $plan) {
            return response()->json([
                'success' => false,
                'message' => 'Paket langganan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail paket langganan berhasil diambil.',
            'data' => $plan,
        ]);
    }
}
