<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiSegmentTest extends TestCase
{
    public function test_the_admin_segment_is_available(): void
    {
        $response = $this->getJson('/api/admin');

        $response->assertOk()
            ->assertJsonPath('segment', 'admin')
            ->assertJsonPath('status', 'ok');
    }

    public function test_the_storefront_segment_is_available(): void
    {
        $response = $this->getJson('/api/storefront');

        $response->assertOk()
            ->assertJsonPath('segment', 'storefront')
            ->assertJsonPath('status', 'ok');
    }
}
