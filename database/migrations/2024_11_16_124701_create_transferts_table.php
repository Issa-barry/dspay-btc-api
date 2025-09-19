<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferts', function (Blueprint $table) {
            $table->id();

            // Liens expéditeur / bénéficiaire
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('beneficiaire_id')->constrained('beneficiaires')->restrictOnDelete();

            // Devises (EUR -> GNF)
            $table->foreignId('devise_source_id')->default(1)->constrained('devises')->restrictOnDelete(); // EUR
            $table->foreignId('devise_cible_id')->default(2)->constrained('devises')->restrictOnDelete();  // GNF

            // Taux : lien + snapshot ENTIER
            $table->foreignId('taux_echange_id')->nullable()->constrained('taux_echanges')->nullOnDelete();
            $table->unsignedInteger('taux_applique'); // ex: 10700

            // Montants
            $table->decimal('montant_euro', 15, 2);      // € avec 2 décimales
            $table->unsignedBigInteger('montant_gnf');   // GNF entier
            $table->unsignedInteger('frais')->default(0);// GNF entier
            $table->unsignedBigInteger('total');         // GNF entier

            // Divers
            $table->string('code', 16)->unique();
            $table->enum('statut', ['envoyé', 'retiré', 'annulé', 'bloqué'])->default('envoyé');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
