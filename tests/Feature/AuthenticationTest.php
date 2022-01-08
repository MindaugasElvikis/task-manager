<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_register(): void
    {
        $response = $this->post('/api/auth/register', [
            'name' => 'test',
            'email' => 'test@example.org',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.org'
        ]);
    }

    public function test_login(): void
    {
        $user = User::factory()->make();
        $user->save();

        $response = $this->post('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('token_type', 'bearer');
        $response->assertJsonPath('user.name', $user->name);
        $response->assertJsonPath('user.email', $user->email);
        $response->assertSee('access_token');
    }

    public function test_login_invalid_credentials(): void
    {
        $user = User::factory()->make();
        $user->save();

        $response = $this->post('/api/auth/login', [
            'email' => $user->email,
            'password' => 'invalid',
        ]);

        $response->assertStatus(401);
    }

    public function test_my_profile(): void
    {
        $user = User::factory()->make();
        $user->save();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);

        $response = $this->get('/api/auth/me', [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('name', $user->name);
        $response->assertJsonPath('email', $user->email);
    }

    public function test_my_profile_unauthorized(): void
    {
        $user = User::factory()->make();
        $user->save();

        $response = $this->get('/api/auth/me', [
            'Authorization' => 'Bearer invalid',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_refresh(): void
    {
        $user = User::factory()->make();
        $user->save();
        $token = auth()->attempt(['email' => $user->email, 'password' => 'password']);

        $response = $this->post('/api/auth/refresh', [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('token_type', 'bearer');
        $response->assertJsonPath('user.name', $user->name);
        $response->assertJsonPath('user.email', $user->email);
        $response->assertSee('access_token');

        $newAccessToken = $response->json('access_token');
        $this->assertNotEquals($token, $newAccessToken);

        $response = $this->get('/api/auth/me', [
            'Authorization' => sprintf('Bearer %s', $newAccessToken),
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('name', $user->name);
        $response->assertJsonPath('email', $user->email);
    }
}
