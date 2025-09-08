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
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devise_source_id')->constrained('devises')->onDelete('cascade');
            $table->foreignId('devise_cible_id')->constrained('devises')->onDelete('cascade');
            $table->decimal('montant_source', 15, 2); // Montant initial dans la devise source
            $table->decimal('montant_converti', 15, 2); // Montant après conversion
            $table->decimal('taux', 10, 4); // Taux appliqué
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
