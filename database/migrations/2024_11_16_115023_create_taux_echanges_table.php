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

            $table->foreignId('devise_source_id')
                  ->constrained('devises')
                  ->restrictOnDelete();

            $table->foreignId('devise_cible_id')
                  ->constrained('devises')
                  ->restrictOnDelete();

            // Taux ENTIER (ex: 10700 = 1 EUR -> 10 700 GNF)
            $table->unsignedInteger('taux');

            $table->timestamps();

            // Un seul taux par paire (source, cible)
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
