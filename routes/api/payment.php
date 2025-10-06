<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payment\Stripe\{
    PaymentIntentStoreController,
    CheckoutSessionStoreController,
    WebhookController,
};

// ───────────────────────────────
// ⚡ Stripe Payment (auth requis)
// ───────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('payments/stripe')
    ->name('payments.stripe.')
    ->group(function () {
        // Création d’un PaymentIntent (Stripe Elements)
        Route::post('create-payment-intent', [PaymentIntentStoreController::class, 'store'])
            ->name('payment-intent.store');

        // Création d’une session Stripe Checkout
        Route::post('checkout-session', [CheckoutSessionStoreController::class, 'store'])
            ->name('checkout.store');

        // Annulation (retour utilisateur via cancel_url)
        Route::get('checkout-session/cancel', [CheckoutSessionStoreController::class, 'cancel'])
            ->name('checkout.cancel');

        // Récupération du statut d’une session Checkout
        Route::get('checkout-session/{sessionId}', [CheckoutSessionStoreController::class, 'show'])
            ->name('checkout.show');
    });

// ───────────────────────────────
// ⚡ Webhook Stripe (public, sans CSRF)
// ───────────────────────────────
Route::post('/payments/stripe/webhook', [WebhookController::class, 'handle'])
    ->name('stripe.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
