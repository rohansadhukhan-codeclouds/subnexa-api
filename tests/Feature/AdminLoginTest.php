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
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.name', 'Admin')
            ->assertJsonPath('user.email', 'admin@subnexa.test')
            ->assertJsonStructure([
                'token',
                'token_type',
                'expires_in',
                'user' => ['name', 'email'],
            ]);

        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertNotSame('', $token);
    }

    public function test_admin_me_requires_bearer_token(): void
    {
        $response = $this->getJson('/api/admin/me');

        $response->assertUnauthorized();
    }

    public function test_admin_can_fetch_me_with_bearer_token(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@subnexa.test',
            'password' => 'admin123',
            'is_admin' => true,
        ]);

        $login = $this->postJson('/api/admin/login', [
            'email' => 'admin@subnexa.test',
            'password' => 'admin123',
        ])->assertOk();

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('name', 'Admin')
            ->assertJsonPath('email', 'admin@subnexa.test')
            ->assertJsonPath('is_admin', true);
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
