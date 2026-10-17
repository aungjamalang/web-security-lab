<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_authenticator_code_enables_two_factor_authentication(): void
    {
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $code = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/v1/auth/two-factor/confirm', [
                'code' => $code,
            ]);

        $response->assertOk()
            ->assertJsonPath(
                'message',
                'Two-factor authentication enabled successfully.'
            )
            ->assertJsonCount(8, 'recovery_codes');

        $user->refresh();

        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertCount(8, $user->two_factor_recovery_codes);
    }

    public function test_invalid_authenticator_code_does_not_enable_two_factor_authentication(): void
    {
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/v1/auth/two-factor/confirm', [
                'code' => '000000',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'The authenticator code is invalid.'
            );

        $user->refresh();

        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_recovery_codes);
    }

    public function test_recovery_code_can_only_be_used_once(): void
    {
        $recoveryCode = str_repeat('a', 20);

        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret',
            'two_factor_recovery_codes' => [$recoveryCode],
            'two_factor_confirmed_at' => now(),
        ]);

        $firstResponse = $this->actingAs($user, 'web')
            ->postJson('/api/v1/auth/two-factor/recovery-code', [
                'recovery_code' => $recoveryCode,
            ]);

        $firstResponse->assertOk()
            ->assertJsonPath('message', 'Recovery code accepted.')
            ->assertJsonPath('remaining_recovery_codes', 0);

        $user->refresh();
        $this->assertSame([], $user->two_factor_recovery_codes);

        $secondResponse = $this->postJson(
            '/api/v1/auth/two-factor/recovery-code',
            ['recovery_code' => $recoveryCode]
        );

        $secondResponse->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'The recovery code is invalid or has already been used.'
            );
    }

    public function test_invalid_recovery_code_is_rejected(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret',
            'two_factor_recovery_codes' => [str_repeat('a', 20)],
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/v1/auth/two-factor/recovery-code', [
                'recovery_code' => str_repeat('b', 20),
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'The recovery code is invalid or has already been used.'
            );

        $user->refresh();
        $this->assertCount(1, $user->two_factor_recovery_codes);
    }



    public function test_two_factor_login_returns_challenge_without_authenticating_user(): void
    {
        $password = 'CorrectPass_2026!';
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => [],
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('two_factor_required', true)
            ->assertJsonStructure(['challenge_token']);

        $this->assertGuest('web');
    }

    public function test_valid_totp_completes_two_factor_login(): void
    {
        $password = 'CorrectPass_2026!';
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => [],
            'two_factor_confirmed_at' => now(),
        ]);

        $challenge = $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ])->assertStatus(202)->json('challenge_token');

        $response = $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/two-factor/login', [
            'challenge_token' => $challenge,
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('user.email', $user->email);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_recovery_code_completes_two_factor_login_only_once(): void
    {
        $password = 'CorrectPass_2026!';
        $recoveryCode = str_repeat('a', 20);
        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => [$recoveryCode],
            'two_factor_confirmed_at' => now(),
        ]);

        $challenge = $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ])->assertStatus(202)->json('challenge_token');

        $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/two-factor/login', [
            'challenge_token' => $challenge,
            'code' => $recoveryCode,
        ])->assertOk();

        $user->refresh();
        $this->assertSame([], $user->two_factor_recovery_codes);

        $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/two-factor/login', [
            'challenge_token' => $challenge,
            'code' => $recoveryCode,
        ])->assertUnauthorized();
    }

}
