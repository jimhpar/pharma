<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_a_sanctum_token_and_creates_a_customer(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '01700000000',
            'device_name' => 'iPhone 17',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.email', 'john@example.com')
            ->assertJsonPath('data.customer.email', 'john@example.com');

        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
            'phone' => '01700000000',
        ]);
    }

    public function test_login_returns_a_sanctum_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
            'device_name' => 'Web Browser',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logout_revokes_the_current_access_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Web Browser');

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertNull(PersonalAccessToken::findToken($token->plainTextToken));
    }
}
