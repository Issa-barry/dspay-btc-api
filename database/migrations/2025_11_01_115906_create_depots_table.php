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
        Schema::create('depots', function (Blueprint $table) {
             $table->id();

            //  Lien avec l'utilisateur
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            //  Données principales du dépôt
            $table->string('serviceId'); // ex: "orange-money", "momo", etc.

            //  Nouveau champ : montant envoyé en euros (avec décimales)
            $table->decimal('montant_envoye', 15, 2); // ex: 50.00 €

            //  Montant reçu (en GNF) → entier, pas de décimales
            $table->unsignedBigInteger('amount'); // ex: 475000 GNF

            //  Informations sur le bénéficiaire et le client
            $table->string('recipientTel');
             $table->string('customerPhoneNumber');

            //  Statut de l'opération
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            //  Référence unique DSP-YYYYMMDD-000X
            $table->string('transaction_ref')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depots');
    }
};
