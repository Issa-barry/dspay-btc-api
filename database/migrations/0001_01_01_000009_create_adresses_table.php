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
        // Vérifie avant création
        if (!Schema::hasTable('adresses')) {
            Schema::create('adresses', function (Blueprint $table) {
                $table->id();

                // relation avec user
                $table->foreignId('user_id')
                      ->constrained('users')
                      ->cascadeOnDelete();

                $table->string('pays');
                $table->string('code')->nullable();
                $table->string('adresse')->nullable();
                $table->string('complement_adresse')->nullable();
                $table->string('code_postal')->nullable();
                $table->string('ville')->nullable();
                $table->string('quartier')->nullable();
                $table->string('region')->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adresses');
    }
};
