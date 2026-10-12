<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Auth;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'web-security-lab-api',
        ]);
    });

    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');;
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/auth/email/verification-notification', [AuthController::class, 'resendVerificationNotification'])
    ->middleware(['auth:sanctum', 'throttle:6,1']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
