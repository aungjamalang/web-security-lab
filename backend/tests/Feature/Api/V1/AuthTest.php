<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'testuser@example.test',
            'password'              => 'TestPass_2026!',
            'password_confirmation' => 'TestPass_2026!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Registration successful.'
            );

        $this->assertDatabaseHas('users', [
            'name'  => 'Test User',
            'email' => 'testuser@example.test',
        ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'unknown@example.test',
            'password' => 'WrongPassword_2026!',
        ]);

        $response
            ->assertStatus(401)
            ->assertExactJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $password = 'CorrectPass_2026!';

        $user = User::factory()->create([
            'email'    => 'loginuser@example.test',
            'password' => Hash::make($password),
        ]);

        $response = $this
            ->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/login', [
                'email'     => $user->email,
                'password'  => $password,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'web')
            ->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertOk()
            ->assertExactJson([
                'message' => 'Logout successful.',
            ]);

        $this->assertGuest('web');
    }

    public function test_guest_cannot_access_authenticated_profile(): void
    {
        $response = $this->getJson('/api/user');

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_authenticated_user_can_access_profile(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'web')
            ->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.test',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Another User',
            'email'                 => 'existing@example.test',
            'password'              => 'TestPass_2026!',
            'password_confirmation' => 'TestPass_2026!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email'     => 'ratelimit@example.test',
                'password'  => 'WrongPassword_2026!',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'email'     => 'ratelimit@example.test',
            'password'  => 'WrongPassword_2026!',
        ])->assertTooManyRequests();
    }

    public function test_registration_stores_a_hashed_password(): void
    {
        $password = 'StrongPass_2026!';

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Hash Test User',
            'email'                 => 'hashtest@example.test',
            'password'              => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertCreated();

        $user = User::where(
            'email',
            'hashtest@example.test'
        )->firstOrFail();

        $this->assertNotSame($password, $user->password);

        $this->assertTrue(
            Hash::check($password, $user->password)
        );
    }

    public function test_registration_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Mismatch Test',
            'email'                 => 'mismatch@example.test',
            'password'              => 'StrongPass_2026!',
            'password_confirmation' => 'DifferentPass_2026!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', [
            'email' => 'mismatch@example.test',
        ]);
    }

    public function test_registration_rejects_short_passwords(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Short Password Test',
            'email'                 => 'shortpass@example.test',
            'password'              => 'Short1!',
            'password_confirmation' => 'Short1!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', [
            'email' => 'shortpass@example.test',
        ]);
    }

    public function test_registration_is_rate_limited_after_five_attempts(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/auth/register', [])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/register', [])
            ->assertTooManyRequests();
    }

    public function test_registration_sends_email_verification_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Verify Test User',
            'email'                 => 'verifytest@example.test',
            'password'              => 'TestPass_2026!',
            'password_confirmation' => 'TestPass_2026!',
        ]);

        $response->assertCreated();

        $user = User::where(
            'email',
            'verifytest@example.test'
        )->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_user_can_verify_email_with_valid_signed_link(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect(
            rtrim(config('app.frontend_url'), '/')
            . '/email/verification-result?status=success'
        );

        $this->assertNotNull(
            $user->fresh()->email_verified_at,
        );
    }

    public function test_tampered_verification_link_is_rejected(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        // Modify the signature to simulate a tampered link.
        $tamperedUrl = str_replace(
            'signature=',
            'signature=invalid',
            $verificationUrl,
        );

        $response = $this->get($tamperedUrl);

        $response->assertRedirect(
            rtrim(config('app.frontend_url'), '/')
            . '/email/verification-result?status=error'
        );

        $this->assertNull(
            $user->fresh()->email_verified_at,
        );
    }

    public function test_expired_verification_link_is_rejected(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(10),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect(
            rtrim(config('app.frontend_url'), '/')
            . '/email/verification-result?status=error'
        );

        $this->assertNull(
            $user->fresh()->email_verified_at,
        );
    }

    public function test_unverified_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this
            ->actingAs($user, 'web')
            ->postJson('/api/v1/auth/email/verification-notification');

        $response
            ->assertOk()
            ->assertExactJson([
                'message' => 'Verification link sent.',
            ]);

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
        );
    }

    public function test_verified_user_cannot_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'web')
            ->postJson('/api/v1/auth/email/verification-notification');

        $response
            ->assertOk()
            ->assertExactJson([
                'message' => 'Email address is already verified.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_guest_cannot_resend_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson(
            '/api/v1/auth/email/verification-notification'
        );

        $response->assertUnauthorized();

        Notification::assertNothingSent();
    }

    public function test_already_verified_user_cannot_reuse_verification_link(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect(
            rtrim(config('app.frontend_url'), '/')
            . '/email/verification-result?status=error'
        );

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}