<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{
    LoginBearerController,
    LogoutController,
    PasswordResetLinkController,
    ResetPasswordController,
    VerifyEmailController,
    ResendVerificationController,
    CheckTokenController,
    LoginStatelessController,
    MeController
};

/*
|--------------------------------------------------------------------------
| Public (pas de sanctum)
|--------------------------------------------------------------------------
*/

Route::post('/login', LoginBearerController::class)->name('auth.login');

// Login Stateless (nouveau - avec expiration)
Route::post('/login-stateless', LoginStatelessController::class)->name('auth.login.stateless');


Route::post('/sendResetPasswordLink', PasswordResetLinkController::class)->name('auth.sendReset');
Route::post('/ResetPassword',        ResetPasswordController::class)->name('auth.reset');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware('signed')
    ->name('verification.verify');

Route::post('/resend-verification-email', ResendVerificationController::class)
    ->name('auth.resendVerification');

/*
|--------------------------------------------------------------------------
| Protégées (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', LogoutController::class)->name('auth.logout');
    Route::get('/check-token-header', CheckTokenController::class)->name('auth.checkToken');
    Route::get('users/me', MeController::class);
    Route::get('test-endpoint', [MeController::class, 'index'])->name('index');
});
