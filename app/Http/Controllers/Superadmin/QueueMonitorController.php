<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QueueMonitorController extends Controller
{
    public function index(): View
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->paginate(50);

        $stats = [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'failed_today' => DB::table('failed_jobs')
                ->whereDate('failed_at', today())
                ->count(),
        ];

        return view('superadmin.system.queue.index', compact('failedJobs', 'stats'));
    }

    public function show(int $id): View
    {
        $job = DB::table('failed_jobs')->where('id', $id)->first();

        if (! $job) {
            abort(404);
        }

        return view('superadmin.system.queue.show', compact('job'));
    }

    public function retry(int $id): RedirectResponse
    {
        $job = DB::table('failed_jobs')->where('id', $id)->first();

        if (! $job) {
            abort(404);
        }

        Artisan::call('queue:retry', ['id' => [$job->uuid]]);

        return redirect()->route('superadmin.system.queue.index')
            ->with('success', 'Job berhasil di-retry.');
    }

    public function retryAll(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return redirect()->route('superadmin.system.queue.index')
            ->with('success', 'Semua failed jobs berhasil di-retry.');
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::table('failed_jobs')->where('id', $id)->delete();

        return redirect()->route('superadmin.system.queue.index')
            ->with('success', 'Failed job berhasil dihapus.');
    }

    public function flush(): RedirectResponse
    {
        Artisan::call('queue:flush');

        return redirect()->route('superadmin.system.queue.index')
            ->with('success', 'Semua failed jobs berhasil dihapus.');
    }
}
