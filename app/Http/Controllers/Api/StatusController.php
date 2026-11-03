<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->databaseConnection();
        $healthy = $database['status'] === 'connected';

        return response()->json([
            'name' => config('app.name'),
            'backend' => 'Laravel '.app()->version(),
            'frontend' => 'Next.js',
            'status' => $healthy ? 'ok' : 'degraded',
            'health' => $healthy ? 'ok' : 'degraded',
            'time' => now()->toIso8601String(),
            'connections' => [
                'database' => $database,
            ],
        ], $healthy ? 200 : 503);
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseConnection(): array
    {
        $name = (string) config('database.default');
        $config = config("database.connections.{$name}");
        $config = is_array($config) ? $config : [];

        // Do not expose host, database name, or credentials publicly.
        $report = [
            'name' => $name,
            'driver' => $config['driver'] ?? null,
            'status' => 'disconnected',
        ];

        try {
            $started = microtime(true);
            DB::connection()->getPdo();
            DB::select('select 1 as ok');
            $report['status'] = 'connected';
            $report['latency_ms'] = round((microtime(true) - $started) * 1000, 2);
        } catch (Throwable $exception) {
            $report['status'] = 'disconnected';
            Log::warning('Database health check failed.', [
                'connection' => $name,
                'driver' => $config['driver'] ?? null,
                'message' => $exception->getMessage(),
            ]);
        }

        return $report;
    }
}
