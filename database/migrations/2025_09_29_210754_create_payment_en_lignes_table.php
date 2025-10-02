<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_en_lignes', function (Blueprint $table) {
            // Engine/convention
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->engine = 'InnoDB';
            }

            $table->id();

            // ── Informations générales ─────────────────────────────────────────────
            $table->string('provider', 32)->index();                // ex: 'stripe', 'paypal'
            $table->string('provider_payment_id', 191)->nullable()->index(); // id "générique" (pi_..., cs_...) pour compat

            // Identifiants Stripe dédiés
            $table->string('session_id', 191)->nullable()->unique();         // cs_...
            $table->string('payment_intent_id', 191)->nullable()->unique();  // pi_...

            // Statut interne
            $table->string('status', 20)->default('pending')->index(); // pending|succeeded|failed|refunded|processing|canceled

            // ── Montants / devise ────────────────────────────────────────────────
            $table->unsignedBigInteger('amount');                      // en centimes
            $table->string('currency', 10)->default('eur')->index();   // 'eur', 'usd', ...

            // ── Métadonnées & relations ─────────────────────────────────────────
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete()->index();

            $table->json('metadata')->nullable();

            // Marqueur de traitement (idempotence)
            $table->timestamp('processed_at')->nullable()->index();

            $table->timestamps();

            // Index utiles pour reporting/listing
            $table->index(['user_id', 'created_at']);
        });

        // (Optionnel) CHECK constraint sur le statut si MySQL ≥ 8.0 / PostgreSQL
        try {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                // MySQL 8+ uniquement (ignorer si version < 8)
                DB::statement("
                    ALTER TABLE `payment_en_lignes`
                    ADD CONSTRAINT `chk_payment_en_lignes_status`
                    CHECK (`status` IN ('pending','succeeded','failed','refunded','processing','canceled'))
                ");
            } elseif ($driver === 'pgsql') {
                DB::statement("
                    ALTER TABLE payment_en_lignes
                    ADD CONSTRAINT chk_payment_en_lignes_status
                    CHECK (status IN ('pending','succeeded','failed','refunded','processing','canceled'))
                ");
            }
        } catch (\Throwable $e) {
            // Silencieux si le SGBD ne supporte pas les CHECKs
        }
    }

    public function down(): void
    {
        // Supprime d'abord la contrainte CHECK si elle existe (best effort)
        try {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `payment_en_lignes` DROP CHECK `chk_payment_en_lignes_status`");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE payment_en_lignes DROP CONSTRAINT IF EXISTS chk_payment_en_lignes_status");
            }
        } catch (\Throwable $e) {
            // no-op
        }

        Schema::dropIfExists('payment_en_lignes');
    }
};
