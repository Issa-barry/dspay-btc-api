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
        Schema::create('transferts', function (Blueprint $table) {
            $table->id();

            // Liens expéditeur / bénéficiaire
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('beneficiaire_id')->constrained('beneficiaires')->restrictOnDelete();

            // Devises (EUR -> GNF)
            $table->foreignId('devise_source_id')
                  ->default(1) // EUR
                  ->constrained('devises')
                  ->restrictOnDelete();

            $table->foreignId('devise_cible_id')
                  ->default(2) // GNF
                  ->constrained('devises')
                  ->restrictOnDelete();

            // Taux : lien + snapshot de la valeur appliquée
            $table->foreignId('taux_echange_id')
                  ->nullable()
                  ->constrained('taux_echanges')
                  ->nullOnDelete();

            $table->decimal('taux_applique', 18, 6); // fige le taux au moment du transfert

            // Montants
            $table->decimal('montant_euro', 15, 2);
            $table->decimal('montant_gnf', 15, 2);
            $table->integer('frais')->default(0);
            $table->decimal('total', 15, 2);

            // Divers
            $table->string('code', 16)->unique();
            $table->string('statut')->default('en_cours');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
