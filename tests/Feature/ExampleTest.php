<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_redirects_to_the_next_frontend(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(config('app.frontend_url'));
    }

    public function test_the_api_status_endpoint_reports_the_stack(): void
    {
        $response = $this->getJson('/api/status');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('health', 'ok')
            ->assertJsonPath('connections.database.status', 'connected')
            ->assertJsonPath('frontend', 'Next.js')
            ->assertJsonPath('name', config('app.name'));
    }

    public function test_the_api_status_endpoint_reports_a_broken_database_connection(): void
    {
        $stderr = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $stderr->shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Database health check failed.'
                    && isset($context['exception_class'], $context['diagnostic'], $context['pdo_pgsql_loaded'])
                    && ! str_contains((string) $context['diagnostic'], 'password=')
                    && ! str_contains((string) $context['diagnostic'], 'postgresql://');
            });

        Log::shouldReceive('channel')
            ->once()
            ->with('stderr')
            ->andReturn($stderr);

        config([
            'database.default' => 'broken',
            'database.connections.broken' => [
                'driver' => 'sqlite',
                'database' => 'C:/missing/subnexa/database.sqlite',
            ],
        ]);

        $response = $this->getJson('/api/status');

        $response->assertStatus(503)
            ->assertJsonPath('health', 'degraded')
            ->assertJsonPath('connections.database.status', 'disconnected')
            ->assertJsonMissingPath('connections.database.host')
            ->assertJsonMissingPath('connections.database.diagnostic');
    }
}
