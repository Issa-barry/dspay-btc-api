<?php

use Illuminate\Support\Facades\Route;

/**
 * Tout fichier ici passe par le middleware "web"
 * (cookies, session, CSRF, etc.)
 */

// Page welcome (optionnelle)
Route::get('/', fn () => view('welcome'));

// ───── Prévisualisation d’e-mails (dev) ─────
use App\Models\Transfert;
Route::get('/preview-email', function () {
    $transfert = Transfert::with(['deviseSource', 'deviseCible'])->first();
    return view('emails.transfertNotification', ['transfert' => $transfert]);
});
Route::get('/preview-email-retrait', function () {
    $transfert = Transfert::with(['deviseSource', 'deviseCible'])->first();
    return view('emails.transfertRetire', ['transfert' => $transfert]);
});

// ───── Auth “WEB” (Angular SPA avec cookies HTTP-only) ─────
use App\Http\Controllers\Auth\{
    LoginWebController, LogoutWebController,  
    VerifyEmailController, ResendVerificationController,
    PasswordResetLinkController, ResetPasswordController,
};

Route::prefix('web')->group(function () {
    // Login via session (pas de token Bearer)
    Route::post('/login', LoginWebController::class)->middleware('guest')->name('web.login');

    // Logout session
       Route::post('/logout', LogoutWebController::class)
        ->middleware('auth:web')
        ->name('web.logout');

    // Profil connecté (session)
    // Route::get('/users/me', MeController::class)->middleware('auth')->name('web.me');

    // Mails: vérification & renvoi
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware('signed')
        ->name('web.verification.verify');

    Route::post('/resend-verification-email', ResendVerificationController::class)
        ->name('web.verification.resend');

    // Mot de passe
    Route::post('/sendResetPasswordLink', PasswordResetLinkController::class)->name('web.password.email');
    Route::post('/ResetPassword', ResetPasswordController::class)->name('web.password.reset');
});

// // Sanctum: cookie CSRF (NE PAS retirer)
// Route::get('/sanctum/csrf-cookie', function () {
//     return response()->noContent();
// });

use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
    ->middleware('web');


    // Route::get('web/me', MeController::class);