<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_en_lignes', function (Blueprint $table) {
            $table->id();

            // ─── Informations générales ───
            $table->string('provider')->index(); // stripe, paypal, etc.

            // Compat: id générique historique (peut être cs_ OU pi_)
            $table->string('provider_payment_id')->nullable()->index();

            // Nouveaux identifiants dédiés Stripe
            $table->string('session_id')->nullable()->index();           // cs_...
            $table->string('payment_intent_id')->nullable()->index();    // pi_...

            $table->string('status')->index(); // succeeded, pending, failed, refunded…

            // ─── Montant et devise ───
            $table->bigInteger('amount'); // en centimes
            $table->string('currency', 10);

            // ─── Métadonnées et relations ───
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('metadata')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unicités "où non null" (si tu veux empêcher les doublons par identifiant Stripe)
            // NB: MySQL autorise plusieurs NULLs dans un index unique, c’est OK.
            $table->unique('session_id');
            $table->unique('payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_en_lignes');
    }
};
