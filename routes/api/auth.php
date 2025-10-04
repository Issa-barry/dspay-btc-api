<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{
    LoginController,
    LogoutController,
    MeController,
    PasswordResetLinkController,
    ResetPasswordController,
    VerifyEmailController,
    ResendVerificationController,
    CheckTokenController
};

/*
|--------------------------------------------------------------------------
| Public (pas de sanctum)
|--------------------------------------------------------------------------
*/
Route::post('/login', LoginController::class)->name('auth.login');

/* NOTE : tu utilises ces URLs côté front, on les garde telles quelles */
Route::post('/sendResetPasswordLink', PasswordResetLinkController::class)->name('auth.sendReset');
Route::post('/ResetPassword',        ResetPasswordController::class)->name('auth.reset');

/* Vérification email via lien signé */
Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware('signed')
    ->name('verification.verify');

/* IMPORTANT : on laisse le renvoi d’email de vérification en PUBLIC.
   Sinon un utilisateur non vérifié ne pourrait pas demander le renvoi. */
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

    /* On garde /users/me ici côté auth, même si tu as un fichier users.php pour le reste */
    Route::get('/users/me', MeController::class)->name('auth.me');
});
