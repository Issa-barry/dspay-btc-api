<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('taux_echanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devise_source_id')->constrained('devises');
            $table->foreignId('devise_cible_id')->constrained('devises');
            $table->decimal('taux', 12, 4); // Le taux de conversion, précision 4 décimales
            $table->timestamps();

            // Index pour une recherche rapide des taux entre devises
            $table->unique(['devise_source_id', 'devise_cible_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taux_echanges');
    }
};
