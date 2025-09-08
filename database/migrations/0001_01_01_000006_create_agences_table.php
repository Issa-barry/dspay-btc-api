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
            $table->string('reference', 6)->unique();
            $table->string('nom_agence');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->enum('statut', ['active', 'attente', 'bloque', 'archive'])->default('attente');
            $table->timestamp('date_creation')->default(now());
            $table->foreignId('adresse_id')->constrained('adresses')->onDelete('cascade');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->onDelete('set null');
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
