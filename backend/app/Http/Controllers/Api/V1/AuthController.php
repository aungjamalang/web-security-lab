<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        AuthService $authService
    ): JsonResponse {
        $user = $authService->register($request->validated());

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
        ], 201);
    }

    public function login(
        LoginRequest $request,
        AuthService $authService
    ): JsonResponse {
        $credentials = $request->validated();

        if (! Auth::guard('web')->validate($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (
            $user
            && $user->two_factor_confirmed_at
            && $user->two_factor_secret
        ) {
            $challengeToken = Str::random(64);

            Cache::put(
                'two_factor_login:' . hash('sha256', $challengeToken),
                $user->id,
                now()->addMinutes(5)
            );

            return response()->json([
                'two_factor_required' => true,
                'challenge_token' => $challengeToken,
                'message' => 'Enter your authenticator or recovery code.',
            ], 202);
        }

        if (! $authService->attemptLogin($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'two_factor_required' => false,
            'message' => 'Login successful.',
            'user' => $request->user(),
        ]);
    }

    public function verifyTwoFactorLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_token' => ['required', 'string', 'size:64'],
            'code' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        $cacheKey = 'two_factor_login:' . hash(
            'sha256',
            $validated['challenge_token']
        );

        $userId = Cache::get($cacheKey);

        if (! $userId) {
            return response()->json([
                'message' => 'The login challenge is invalid or expired.',
            ], 401);
        }

        $user = User::find($userId);

        if (
            ! $user
            || ! $user->two_factor_confirmed_at
            || ! $user->two_factor_secret
        ) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'The login challenge is invalid or expired.',
            ], 401);
        }

        $code = $validated['code'];
        $google2fa = app('pragmarx.google2fa');

        $validTotp = preg_match('/^\d{6}$/', $code)
            && $google2fa->verifyKey($user->two_factor_secret, $code);

        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        $matchedRecoveryIndex = null;

        if (! $validTotp) {
            foreach ($recoveryCodes as $index => $recoveryCode) {
                if (hash_equals($recoveryCode, $code)) {
                    $matchedRecoveryIndex = $index;
                    break;
                }
            }
        }

        if (! $validTotp && $matchedRecoveryIndex === null) {
            return response()->json([
                'message' => 'Invalid authentication code.',
            ], 422);
        }

        if ($matchedRecoveryIndex !== null) {
            array_splice($recoveryCodes, $matchedRecoveryIndex, 1);
            $user->two_factor_recovery_codes = $recoveryCodes;
            $user->save();
        }

        // Consume the challenge before establishing the authenticated session.
        Cache::forget($cacheKey);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function resendVerificationNotification(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email address is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
                'current_password',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:current_password',
            ],
        ]);

        $user = $request->user();

        $user->password = $validated['password'];
        $user->save();

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If an account exists for that email, a password reset link will be sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Your password has been reset successfully.',
            ]);
        }

        return response()->json([
            'message' => 'This password reset link is invalid or has expired.',
        ], 422);
    }

    public function beginTwoFactorSetup(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => [
                'required',
                'string',
                'current_password:web',
            ],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->two_factor_confirmed_at !== null) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled.',
            ], 409);
        }

        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $issuer = config('app.name');
        $label = rawurlencode($issuer . ':' . $user->email);

        $otpauthUrl = 'otpauth://totp/' . $label . '?'
            . http_build_query([
                'secret' => $secret,
                'issuer' => $issuer,
                'algorithm' => 'SHA1',
                'digits' => 6,
                'period' => 30,
            ], '', '&', PHP_QUERY_RFC3986);

        return response()->json([
            'message' => 'Scan the authenticator setup URI, then confirm a code to enable two-factor authentication.',
            'secret' => $secret,
            'otpauth_url' => $otpauthUrl,
        ]);
    }

    public function confirmTwoFactorSetup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->two_factor_confirmed_at !== null) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled.',
            ], 409);
        }

        if (! $user->two_factor_secret) {
            return response()->json([
                'message' => 'Start two-factor setup before confirming a code.',
            ], 422);
        }

        $google2fa = app('pragmarx.google2fa');

        if (! $google2fa->verifyKey(
            $user->two_factor_secret,
            $validated['code']
        )) {
            return response()->json([
                'message' => 'The authenticator code is invalid.',
            ], 422);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => Str::random(10) . Str::random(10))
            ->all();

        $user->forceFill([
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }
    public function verifyRecoveryCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recovery_code' => ['required', 'string', 'min:20', 'max:100'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (
            $user->two_factor_confirmed_at === null
            || ! is_array($user->two_factor_recovery_codes)
        ) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], 409);
        }

        $codes = $user->two_factor_recovery_codes;
        $providedCode = $validated['recovery_code'];
        $matchedIndex = null;

        foreach ($codes as $index => $storedCode) {
            if (is_string($storedCode) && hash_equals($storedCode, $providedCode)) {
                $matchedIndex = $index;
                break;
            }
        }

        if ($matchedIndex === null) {
            return response()->json([
                'message' => 'The recovery code is invalid or has already been used.',
            ], 422);
        }

        unset($codes[$matchedIndex]);

        $user->forceFill([
            'two_factor_recovery_codes' => array_values($codes),
        ])->save();

        return response()->json([
            'message' => 'Recovery code accepted.',
            'remaining_recovery_codes' => count($codes),
        ]);
    }
}