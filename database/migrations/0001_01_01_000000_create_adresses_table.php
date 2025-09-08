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
        Schema::create('adresses', function (Blueprint $table) {
            if (!Schema::hasTable('adresses')) {
            $table->id();
            $table->string('pays');
            $table->string('adresse');
            $table->string('complement_adresse')->nullable();
            $table->string('code_postal');
            $table->string('ville');
            $table->string('quartier')->nullable();
            $table->string('region')->nullable();
            $table->timestamps();
        }
        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adresses');
    }
};
