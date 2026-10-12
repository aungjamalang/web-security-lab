<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', function (
    string $id,
    string $hash
) {
    $user = User::findOrFail($id);

    abort_unless(
        hash_equals(
            sha1($user->getEmailForVerification()),
            $hash
        ),
        403,
        'Invalid verification link.'
    );

    if ($user->hasVerifiedEmail()) {
        return redirect()->away(
            rtrim(config('app.frontend_url'), '/')
            . '/email/verification-result?status=error'
        );
    }

    $user->markEmailAsVerified();

    event(new Verified($user));

    return redirect()->away(
        rtrim(config('app.frontend_url'), '/')
        . '/email/verification-result?status=success'
    );
})->middleware(['signed', 'throttle:6,1'])
  ->name('verification.verify');