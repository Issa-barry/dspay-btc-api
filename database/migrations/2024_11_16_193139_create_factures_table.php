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
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfert_id')->constrained(); 
            $table->enum('type', ['transfert']); 
            $table->enum('statut', ['brouillon', 'payé', 'partiel'])->default('brouillon'); 
            $table->boolean('envoye')->default(false); 
            $table->string('nom_societe');
            $table->string('adresse_societe');
            $table->string('phone_societe');
            $table->string('email_societe');
            $table->decimal('total', 10, 2); 
            $table->decimal('montant_du', 10, 2); // Montant dû
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
