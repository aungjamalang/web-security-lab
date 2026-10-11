<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function register(array $data): User
    {
        return User::create($data);
    }

    public function attemptLogin(array $credentials): bool
    {
        return Auth::guard('web')->attempt($credentials);
    }
}