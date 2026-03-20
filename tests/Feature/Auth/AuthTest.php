<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'message', 'data' => [
                'id', 'name', 'email', 'token', 'budget_tracking_code',
            ]]);
    }

    public function test_register_creates_budget_tracking_with_unique_code(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Mark Sarabia',
            'email'                 => 'mark@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $code = $response->json('data.budget_tracking_code');

        // Code must be an 8-character uppercase alphanumeric string
        $this->assertNotNull($code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code);

        // The budget tracking record must exist in the DB
        $this->assertDatabaseHas('budget_trackings', ['join_code' => $code]);

        // The user is enrolled as owner
        $userId = $response->json('data.id');
        $this->assertDatabaseHas('budget_tracking_members', [
            'user_id' => $userId,
            'role'    => 'owner',
        ]);
    }

    public function test_two_users_get_different_budget_tracking_codes(): void
    {
        $r1 = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alice', 'email' => 'alice@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);
        $r2 = $this->postJson('/api/v1/auth/register', [
            'name' => 'Bob', 'email' => 'bob@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $this->assertNotEquals(
            $r1->json('data.budget_tracking_code'),
            $r2->json('data.budget_tracking_code')
        );
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $response = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertOk()->assertJsonStructure(['success', 'data' => ['token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong']);
        $response->assertStatus(422);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/logout');
        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');
        $response->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_register_validation_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', ['name' => 'Test', 'password' => 'password123', 'password_confirmation' => 'password123']);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_register_validation_requires_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_register_validation_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com']);
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_me_response_has_correct_structure(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');
        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['id', 'name', 'email']]);
    }
}
