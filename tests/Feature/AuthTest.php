<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_bearer_token(): void
    {
        User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'owner@example.com',
            'password' => 'secret-password',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['token_type', 'access_token']);
    }

    public function test_translations_require_authentication(): void
    {
        $this->getJson('/api/translations')->assertUnauthorized();
    }
}