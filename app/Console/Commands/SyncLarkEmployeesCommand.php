<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Lark\LarkEmployeeService;
use Illuminate\Console\Command;

class SyncLarkEmployeesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lark:sync-employees {--company= : Company ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync and match employees with Lark User IDs based on email';

    /**
     * Execute the console command.
     */
    public function handle(LarkEmployeeService $employeeService): int
    {
        $companyId = $this->option('company');

        $query = Company::whereHas('larkIntegration', function ($q) {
            $q->where('is_active', true);
        });

        if ($companyId) {
            $query->where('id', $companyId);
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->info('Tidak ada perusahaan dengan integrasi Lark yang aktif.');

            return self::SUCCESS;
        }

        foreach ($companies as $company) {
            $this->info("Memproses sinkronisasi karyawan untuk perusahaan: {$company->name} (ID: {$company->id})");

            try {
                $result = $employeeService->syncEmployees($company);
                $this->info("Selesai: {$result['matched']} cocok, {$result['unmatched']} belum cocok dari total {$result['total']} karyawan.");
            } catch (\Exception $e) {
                $this->error("Gagal sinkronisasi untuk perusahaan {$company->name}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
