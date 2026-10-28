<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\ReimbursementCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReimbursementCategoryController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = ReimbursementCategory::withCount('reimbursements')
            ->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $categories = $query->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($categories, 'Daftar kategori reimbursement berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reimbursement_categories', 'name')->where('company_id', $companyId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'requires_receipt' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = ReimbursementCategory::create([
            ...$validated,
            'company_id' => $companyId,
            'requires_receipt' => $request->boolean('requires_receipt', true),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return $this->successResponse($category, 'Kategori reimbursement berhasil ditambahkan', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $category = ReimbursementCategory::withCount('reimbursements')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($category, 'Detail kategori reimbursement berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $category = ReimbursementCategory::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('reimbursement_categories', 'name')->where('company_id', $companyId)->ignore($category->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'requires_receipt' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('requires_receipt')) {
            $validated['requires_receipt'] = $request->boolean('requires_receipt');
        }
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $category->update($validated);

        return $this->successResponse($category->fresh(), 'Kategori reimbursement berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $category = ReimbursementCategory::where('company_id', $companyId)->findOrFail($id);

        if ($category->reimbursements()->count() > 0) {
            return $this->errorResponse('Kategori tidak dapat dihapus karena masih memiliki riwayat klaim', 422);
        }

        $category->delete();

        return $this->successResponse(null, 'Kategori reimbursement berhasil dihapus');
    }
}
