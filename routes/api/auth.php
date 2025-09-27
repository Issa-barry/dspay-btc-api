<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->name('auth.register');
    Route::post('/login', 'login')->name('auth.login');
    Route::post('/ResetPassword', 'resetPassword')->name('auth.reset');
    Route::post('/sendResetPasswordLink', 'sendResetPasswordLink')->name('auth.sendReset');
    Route::get('/verify-email/{id}/{hash}', 'verifyEmail')->middleware('signed')->name('verification.verify');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/check-token-header', [AuthController::class, 'checkTokenInHeader'])->name('auth.checkToken');
    Route::get('users/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/resend-verification-email', [AuthController::class, 'resendVerificationEmail'])->name('auth.resendVerification');
});
