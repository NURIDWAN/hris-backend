<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class AdminBaseController extends Controller
{
    protected function getCompanyId(Request $request): int
    {
        $user = $request->user();

        // Khusus Superadmin: izinkan context switching via X-Company-Id header atau query param
        if ($user && $user->isSuperAdmin()) {
            $requestedCompanyId = $request->header('X-Company-Id') ?: $request->query('company_id');
            if ($requestedCompanyId && Company::where('id', $requestedCompanyId)->exists()) {
                return (int) $requestedCompanyId;
            }
        }

        // Admin & User biasa: terkunci mutlak pada company_id miliknya
        return (int) ($user?->company_id ?: Company::value('id') ?: 1);
    }

    protected function getCompany(Request $request): ?Company
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        if ($user && ! $user->isSuperAdmin() && $user->company) {
            return $user->company;
        }

        return Company::find($companyId) ?? Company::first();
    }

    protected function successResponse(mixed $data = null, string $message = 'Success', int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Data berhasil diambil'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function errorResponse(string $message = 'Error', int $status = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
