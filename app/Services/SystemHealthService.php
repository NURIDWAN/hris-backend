<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthService
{
    public function collect(): array
    {
        return [
            'php' => $this->phpInfo(),
            'laravel' => $this->laravelInfo(),
            'server' => $this->serverInfo(),
            'disk' => $this->diskInfo(),
            'database' => $this->databaseInfo(),
            'cache' => $this->cacheInfo(),
            'queue' => $this->queueInfo(),
            'config' => $this->configInfo(),
        ];
    }

    protected function phpInfo(): array
    {
        return [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => get_loaded_extensions(),
        ];
    }

    protected function laravelInfo(): array
    {
        return [
            'version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'maintenance_mode' => app()->isDownForMaintenance(),
        ];
    }

    protected function serverInfo(): array
    {
        return [
            'os' => PHP_OS_FAMILY,
            'hostname' => gethostname(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'php_sapi' => PHP_SAPI,
        ];
    }

    protected function diskInfo(): array
    {
        $path = base_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;

        return [
            'total' => $total,
            'free' => $free,
            'used' => $used,
            'percentage' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
            'total_formatted' => $this->formatBytes($total),
            'free_formatted' => $this->formatBytes($free),
            'used_formatted' => $this->formatBytes($used),
        ];
    }

    protected function databaseInfo(): array
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            $size = 0;
            $tableCount = 0;

            if ($driver === 'mysql') {
                $dbName = $connection->getDatabaseName();
                $result = DB::select('SELECT SUM(data_length + index_length) as size, COUNT(*) as tables FROM information_schema.tables WHERE table_schema = ?', [$dbName]);
                $size = $result[0]->size ?? 0;
                $tableCount = $result[0]->tables ?? 0;
            }

            return [
                'connected' => true,
                'driver' => $driver,
                'database' => $connection->getDatabaseName(),
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'table_count' => $tableCount,
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'driver' => config('database.default'),
                'database' => '',
                'size' => 0,
                'size_formatted' => '0 B',
                'table_count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function cacheInfo(): array
    {
        $driver = config('cache.default');
        $working = false;

        try {
            Cache::put('health_check', true, 5);
            $working = Cache::get('health_check') === true;
            Cache::forget('health_check');
        } catch (\Exception $e) {
            // Cache not working
        }

        return [
            'driver' => $driver,
            'working' => $working,
        ];
    }

    protected function queueInfo(): array
    {
        $driver = config('queue.default');
        $pendingCount = 0;
        $failedCount = 0;

        try {
            $pendingCount = DB::table('jobs')->count();
        } catch (\Exception $e) {
            // jobs table might not exist
        }

        try {
            $failedCount = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            // failed_jobs table might not exist
        }

        return [
            'driver' => $driver,
            'pending' => $pendingCount,
            'failed' => $failedCount,
        ];
    }

    protected function configInfo(): array
    {
        return [
            'debug' => config('app.debug'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'mail_driver' => config('mail.default'),
            'filesystem_driver' => config('filesystems.default'),
        ];
    }

    protected function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
