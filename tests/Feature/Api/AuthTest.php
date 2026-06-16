<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email'    => 'test@canam.ml',
            'password' => bcrypt('Password123!'),
            'statut'   => 'ACTIF',
            'nom'      => 'Test',
            'prenom'   => 'User',
            'type_compte' => 'CANAM',
        ], $attributes));
    }

    public function test_login_with_valid_credentials(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@canam.ml',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'accessToken',
                'userData' => ['id', 'email', 'nom', 'prenom'],
                'userAbilityRules',
            ]);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@canam.ml',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_inactive_account(): void
    {
        $this->createUser(['statut' => 'DESACTIVE']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@canam.ml',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_login_with_suspended_account(): void
    {
        $this->createUser(['statut' => 'SUSPENDU']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@canam.ml',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }

    public function test_logout_with_authenticated_user(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $response->assertStatus(200);
    }

    public function test_change_password(): void
    {
        $user = $this->createUser(['must_change_password' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/change-password', [
                'current_password' => 'Password123!',
                'new_password'     => 'NewPassword456!',
                'new_password_confirmation' => 'NewPassword456!',
            ]);

        $response->assertStatus(200);
    }
}
