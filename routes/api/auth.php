<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{
    LoginController,
    LogoutController,
    PasswordResetLinkController,
    ResetPasswordController,
    VerifyEmailController,
    ResendVerificationController,
    CheckTokenController,
    MeController
};

/*
|--------------------------------------------------------------------------
| Public (pas de sanctum)
|--------------------------------------------------------------------------
*/
Route::post('/login', LoginController::class)->name('auth.login');

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
    Route::post('/logout',            LogoutController::class)->name('auth.logout');
    Route::get ('/check-token-header', CheckTokenController::class)->name('auth.checkToken');

Route::middleware(['auth:sanctum'])
    ->get('users/me', MeController::class);


 Route::get('test-endpoint', [MeController::class, 'index'])->name('index');

});

  
 