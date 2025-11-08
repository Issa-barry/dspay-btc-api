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
            $table->decimal('montant_envoie', 15, 2);        // Montant saisi en €
            $table->decimal('frais', 10, 2)->default(0); // Frais en € (jamais convertis)
            $table->decimal('total_ttc', 12, 2);           // montant_envoie + frais (en €)

            $table->unsignedBigInteger('amount');     // Montant reçu (GNF entier)
            $table->unsignedBigInteger('total_gnf');       // = amount (pas de frais en GNF)

            // Divers
            $table->string('code', 16)->unique();
            $table->enum('statut', ['envoyé', 'retiré', 'annulé', 'bloqué'])->default('envoyé');

            // Ajoute le mode d’envoi avec une valeur par défaut
            $table->enum('serviceId', ['orange_money', 'ks_pay', 'paycard', 'soutrat_money', 'kulu', 'momo'])->default('orange_money');

            // Informations de destinataire et client
            $table->string('recipientTel', 20)->nullable();        // Numéro de téléphone du bénéficiaire (pour services mobile money)
            $table->string('accountId', 50)->nullable();           // Numéro de compte du bénéficiaire (pour services bancaires)
            $table->string('customerPhoneNumber', 20)->nullable(); // Numéro de téléphone du client (obligatoire si accountId est utilisé)


            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
