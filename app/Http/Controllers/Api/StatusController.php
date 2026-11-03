<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;
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

        // Public payload stays minimal — no host, credentials, or exception text.
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
            $this->logDatabaseFailure($exception, $name, $config);
        }

        return $report;
    }

    /**
     * Always write diagnostics to stderr so Render runtime logs capture them,
     * even when LOG_CHANNEL / cached config points at a file channel.
     *
     * @param  array<string, mixed>  $config
     */
    private function logDatabaseFailure(Throwable $exception, string $connection, array $config): void
    {
        $context = [
            'exception_class' => $exception::class,
            'error_code' => $this->safeErrorCode($exception),
            'diagnostic' => $this->sanitizeDiagnostic($exception->getMessage()),
            'connection' => $connection,
            'driver' => $config['driver'] ?? null,
            'host_configured' => filled($config['host'] ?? null),
            'port' => $config['port'] ?? null,
            'database_configured' => filled($config['database'] ?? null),
            'username_configured' => filled($config['username'] ?? null),
            'password_configured' => filled($config['password'] ?? null),
            'db_url_configured' => filled(config('database.connections.'.$connection.'.url') ?? env('DB_URL')),
            'sslmode' => $config['sslmode'] ?? null,
            'channel_binding' => $config['channel_binding'] ?? null,
            'pdo_pgsql_loaded' => extension_loaded('pdo_pgsql'),
            'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
            'default_log_channel' => config('logging.default'),
        ];

        // Explicit stderr — do not rely on LOG_CHANNEL (often cached as "stack"/"single" on disk).
        Log::channel('stderr')->error('Database health check failed.', $context);
    }

    private function safeErrorCode(Throwable $exception): string|int|null
    {
        if ($exception instanceof PDOException) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if (is_string($sqlState) && $sqlState !== '') {
                return $sqlState;
            }
        }

        $code = $exception->getCode();

        return is_string($code) || is_int($code) ? $code : null;
    }

    /**
     * Strip credentials and connection URIs from driver messages before logging.
     */
    private function sanitizeDiagnostic(string $message): string
    {
        $sanitized = $message;

        // postgresql://user:pass@host / mysql://...
        $sanitized = (string) preg_replace(
            '#[a-z][a-z0-9+.-]*://[^\s\'"]+#i',
            '[redacted-uri]',
            $sanitized,
        );

        // password=secret / pwd=secret in DSN-style text
        $sanitized = (string) preg_replace(
            '/(password|pwd|passwd|user|username|token|secret|api[_-]?key)\s*[:=]\s*([^\s;\'"]+)/i',
            '$1=[redacted]',
            $sanitized,
        );

        // base64 APP_KEY-like blobs accidentally present in messages
        $sanitized = (string) preg_replace('/base64:[A-Za-z0-9+\/=]+/', 'base64:[redacted]', $sanitized);

        return trim($sanitized) !== '' ? $sanitized : 'Database connection failed (no safe diagnostic message).';
    }
}
