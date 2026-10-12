<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        AuthService $authService
    ): JsonResponse {
        $user = $authService->register($request->validated());

        // Send Laravel's signed email-verification link.
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
        if (! $authService->attemptLogin($request->validated())) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful',
            'user'    => $request->user(),
        ]);
    }

    public function logout(Request $request) : JsonResponse
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

        if (! $user instanceof \App\Models\User) {
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
}
