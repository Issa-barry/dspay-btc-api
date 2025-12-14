<?php

use App\Http\Controllers\Auth\CheckTokenController;
use App\Http\Controllers\Auth\LoginBearerController;
use App\Http\Controllers\Auth\LoginStatelessController;
use App\Http\Controllers\Auth\LogoutController;
 use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\ResendVerificationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
 
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
 });

 