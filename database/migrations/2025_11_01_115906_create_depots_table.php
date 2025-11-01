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
             $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 🔗 lien vers l'utilisateur
            $table->string('serviceId'); // ex: "orange-money", "momo", etc.
            $table->decimal('amount', 15, 2);
            $table->string('recipientTel'); // numéro du bénéficiaire
            $table->string('accountId'); // identifiant du compte ou wallet à créditer
            $table->string('customerPhoneNumber'); // numéro du client qui dépose
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending'); // enum pour le statut
            $table->string('transaction_ref')->unique(); // référence unique du dépôt
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
