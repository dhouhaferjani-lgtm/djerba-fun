<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ]);
    }

    /**
     * Detailed health check endpoint (admin only).
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'memory' => $this->checkMemory(),
        ];

        $overallStatus = collect($checks)->every(fn ($check) => $check['status'] === 'ok') ? 'healthy' : 'degraded';

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => $checks,
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $time = DB::select('SELECT NOW()')[0]->now ?? null;

            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'server_time' => $time,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity.
     */
    protected function checkRedis(): array
    {
        try {
            Redis::ping();

            return [
                'status' => 'ok',
                'message' => 'Redis connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue worker status.
     */
    protected function checkQueue(): array
    {
        try {
            // Check if Horizon is running by checking if we can get the status
            $queueSize = Redis::llen('queues:default');

            return [
                'status' => 'ok',
                'message' => 'Queue system operational',
                'pending_jobs' => $queueSize,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Cannot determine queue status',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage disk space.
     */
    protected function checkStorage(): array
    {
        try {
            $path = storage_path();
            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);
            $usedSpace = $totalSpace - $freeSpace;
            $usedPercentage = ($usedSpace / $totalSpace) * 100;

            $status = $usedPercentage > 90 ? 'warning' : 'ok';

            return [
                'status' => $status,
                'message' => 'Storage disk space checked',
                'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'used_percent' => round($usedPercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cannot check storage',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check memory usage.
     */
    protected function checkMemory(): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            $memoryPercentage = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

            $status = $memoryPercentage > 90 ? 'warning' : 'ok';

            return [
                'status' => $status,
                'message' => 'Memory usage checked',
                'used_mb' => round($memoryUsage / 1024 / 1024, 2),
                'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                'used_percent' => round($memoryPercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cannot check memory',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse memory limit string to bytes.
     */
    protected function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $limit,
        };
    }
}
