<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials_and_receives_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@kampuslms.test',
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@kampuslms.test',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'nim_nip',
                    'created_at',
                ],
            ]);

        // Pastikan tidak ada field sensitif yang bocor
        $response->assertJsonMissing(['password'])
            ->assertJsonMissing(['remember_token']);
    }

    public function test_login_fails_with_invalid_password_returns_generic_error(): void
    {
        $user = User::factory()->create([
            'email' => 'test@kampuslms.test',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@kampuslms.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['Email atau kata sandi salah.'],
            ]);
    }

    public function test_login_fails_with_non_existent_email_returns_identical_generic_error(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@kampuslms.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['Email atau kata sandi salah.'],
            ]);
    }

    public function test_authenticated_user_can_view_profile_via_me_endpoint(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'mahasiswa',
                ],
            ]);
    }

    public function test_unauthenticated_request_to_me_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized();
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->assertCount(1, $user->tokens);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Berhasil logout.']);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
