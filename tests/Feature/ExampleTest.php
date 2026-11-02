<?php

namespace Tests\Feature;

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
            ->assertJsonPath('connections.database.status', 'disconnected');
    }
}
