<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payment\Stripe\{
    PaymentIntentStoreController,
    CheckoutSessionStoreController,
    WebhookController,
};

// ─── Paiement (auth obligatoire) ───
Route::middleware(['auth:sanctum','throttle:60,1'])
    ->prefix('payments/stripe')
    ->name('payments.stripe.')
    ->group(function () {
        // Création PaymentIntent (Stripe Elements)
        Route::post('create-payment-intent', [PaymentIntentStoreController::class, 'store'])
            ->name('payment-intent.store');

        // Création d'une session Stripe Checkout
        Route::post('checkout-session', [CheckoutSessionStoreController::class, 'store'])
            ->name('checkout.store');
    });

Route::post('/payments/stripe/webhook', [WebhookController::class, 'handle'])
    ->name('stripe.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
