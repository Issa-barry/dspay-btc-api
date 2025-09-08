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
            // Informations de transfert
            $table->decimal('montant_expediteur', 15, 2);
            $table->decimal('montant_receveur', 15, 2);
            $table->decimal('total', 15, 2);
            $table->integer('frais')->default(0);
            $table->string('code', 255)->unique();  // Code unique pour chaque transfert
            $table->string('statut')->default('en_cours'); // Défaut: en_cours
            $table->string('quartier')->nullable();

            // Informations sur le receveur
            $table->string('receveur_nom_complet');
            $table->string('receveur_phone');

            // Informations sur l'expéditeur
            $table->string('expediteur_nom_complet');
            $table->string('expediteur_phone');
            $table->string('expediteur_email')->nullable();

            // liens
            $table->foreignId('devise_source_id')->constrained('devises')->onDelete('cascade');
            $table->foreignId('devise_cible_id')->constrained('devises')->onDelete('cascade');
            $table->foreignId('taux_echange_id')->nullable()->constrained('taux_echanges')->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');

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
