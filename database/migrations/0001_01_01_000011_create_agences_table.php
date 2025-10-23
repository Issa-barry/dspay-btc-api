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
        Schema::create('agences', function (Blueprint $table) {
            $table->id();

            // 6 caractères (2 lettres + 4 chiffres), unique
            $table->string('reference', 6)->unique();

            $table->string('nom');
            $table->string('phone')->unique();
            $table->string('email')->unique();

            $table->enum('statut', ['active', 'attente', 'bloque', 'archive'])
                  ->default('attente');

            // Adresse non séparée
            $table->string('pays');
            $table->string('ville');
            $table->string('quartier');
             $table->timestamps();
        });
    }

    /** 
     * Reverse the migrations. 
     */
    public function down(): void
    {
        Schema::dropIfExists('agences');
    }
};
