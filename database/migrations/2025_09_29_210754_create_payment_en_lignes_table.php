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
            $table->string('provider_payment_id')->unique();
            $table->string('status')->index(); // succeeded, pending, failed, refunded…

            // ─── Montant et devise ───
            $table->bigInteger('amount'); // en centimes
            $table->string('currency', 10);

            // ─── Métadonnées et relations ───
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_en_lignes');
    }
};
