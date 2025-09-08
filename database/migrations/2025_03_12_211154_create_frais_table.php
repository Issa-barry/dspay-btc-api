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
        Schema::create('frais', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->enum('type', ['fixe', 'pourcentage']);
            $table->decimal('valeur', 10, 2);
            $table->decimal('montant_min', 10, 2)->default(0);
            $table->decimal('montant_max', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frais');
    }
};
