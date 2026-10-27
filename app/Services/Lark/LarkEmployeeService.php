<?php

namespace App\Services\Lark;

use App\Models\Company;
use App\Models\Employee;
use Exception;
use Illuminate\Support\Facades\Log;

class LarkEmployeeService
{
    public function __construct(
        protected LarkApiClient $apiClient
    ) {}

    /**
     * Batch resolve and map active employees for a company based on their email.
     */
    public function syncEmployees(Company $company): array
    {
        $integration = $company->larkIntegration;
        if (! $integration || ! $integration->is_active) {
            throw new Exception('Integrasi Lark tidak aktif untuk perusahaan ini.');
        }

        $employees = $company->employees()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        $matchedCount = 0;
        $unmatchedCount = 0;

        // Lark batch_get_id limit is 50 users per request
        $chunks = $employees->chunk(50);

        foreach ($chunks as $chunk) {
            $emails = $chunk->pluck('email')->filter()->values()->all();
            if (empty($emails)) {
                continue;
            }

            try {
                $response = $this->apiClient->post(
                    $integration,
                    'contact/v3/users/batch_get_id?user_id_type=user_id',
                    ['emails' => $emails]
                );

                $userList = $response['data']['user_list'] ?? [];
                $emailMap = [];
                foreach ($userList as $item) {
                    if (! empty($item['email']) && ! empty($item['user_id'])) {
                        $emailMap[strtolower($item['email'])] = [
                            'user_id' => $item['user_id'],
                            'open_id' => $item['open_id'] ?? null,
                        ];
                    }
                }

                foreach ($chunk as $employee) {
                    $normEmail = strtolower($employee->email);
                    if (isset($emailMap[$normEmail])) {
                        $employee->update([
                            'lark_user_id' => $emailMap[$normEmail]['user_id'],
                            'lark_open_id' => $emailMap[$normEmail]['open_id'],
                        ]);
                        $matchedCount++;
                    } else {
                        $unmatchedCount++;
                    }
                }
            } catch (Exception $e) {
                Log::warning('Failed chunk batch_get_id in LarkEmployeeService', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $integration->update(['last_synced_at' => now()]);

        return [
            'total' => $employees->count(),
            'matched' => $matchedCount,
            'unmatched' => $unmatchedCount,
        ];
    }

    /**
     * Manually map an employee to a Lark User ID.
     */
    public function mapEmployeeManually(Employee $employee, string $larkUserId, ?string $larkOpenId = null): bool
    {
        return $employee->update([
            'lark_user_id' => trim($larkUserId),
            'lark_open_id' => $larkOpenId ? trim($larkOpenId) : $employee->lark_open_id,
        ]);
    }

    /**
     * Unmap an employee from Lark.
     */
    public function unmapEmployee(Employee $employee): bool
    {
        return $employee->update([
            'lark_user_id' => null,
            'lark_open_id' => null,
        ]);
    }
}
