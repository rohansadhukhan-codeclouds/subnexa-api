<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_sign_in(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@subnexa.test',
            'password' => 'admin123',
            'is_admin' => true,
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@subnexa.test',
            'password' => 'admin123',
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Admin')
            ->assertJsonPath('email', 'admin@subnexa.test');
    }

    public function test_a_non_admin_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'user@subnexa.test',
            'password' => 'admin123',
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'user@subnexa.test',
            'password' => 'admin123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
