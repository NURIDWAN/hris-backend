<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Lark\LarkAttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RetryFailedLarkAttendanceSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lark:retry-sync {--company= : Company ID} {--date= : Specific date YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry pushing unsynced attendances to Lark';

    /**
     * Execute the console command.
     */
    public function handle(LarkAttendanceService $attendanceService): int
    {
        $companyId = $this->option('company');
        $dateParam = $this->option('date');
        $date = $dateParam ? Carbon::parse($dateParam) : null;

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
            $targetDate = $date ?? $company->today();
            $this->info("Mengirim ulang absensi tertunda untuk perusahaan {$company->name} pada tanggal {$targetDate->toDateString()}");

            try {
                $result = $attendanceService->retryUnsyncedAttendances($company, $targetDate);
                $this->info("Hasil: {$result['synced']} berhasil, {$result['failed']} gagal, {$result['skipped']} dilewati dari total {$result['total']} rekaman.");
            } catch (\Exception $e) {
                $this->error("Error saat memproses perusahaan {$company->name}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
