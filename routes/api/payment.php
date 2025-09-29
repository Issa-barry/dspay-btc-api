<?php

 use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Payment\Stripe\{
    PaymentIntentStoreController,
    CheckoutSessionStoreController,
    WebhookHandleController
};

Route::middleware(['auth:sanctum','throttle:60,1'])
    ->prefix('payments/stripe')->name('payments.stripe.')
    ->group(function () {
        // ─── Création PaymentIntent (paiement via Stripe Elements) ───
        Route::post('create-payment-intent', [PaymentIntentStoreController::class, 'store'])->name('payment-intent.store');

        // ─── Création d'une session Stripe Checkout ───
        Route::post('checkout-session', [CheckoutSessionStoreController::class, 'store'])->name('checkout.store');

        // ─── Webhook Stripe (⚠️ pas d’auth, Stripe appelle en externe) ───
        Route::post('webhook', [WebhookHandleController::class, 'handle'])->withoutMiddleware(['auth:sanctum'])->name('webhook.handle');
    });
