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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 5)->unique();
            $table->string('nom', 100)->nullable();
            $table->string('prenom', 150)->nullable();
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
             $table->enum('statut', ['active', 'attente', 'bloque', 'archive'])->default('attente');
            $table->date('date_naissance')->default('9999-12-31');
            $table->enum('civilite', ['Mr', 'Mme', 'Mlle', 'Autre'])->default('Autre');
            $table->string('password'); 
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict')->default(1);
             $table->foreignId('adresse_id')->nullable()->constrained('adresses')->nullOnDelete();

    
            // Ajout de la clé étrangère vers la table agences
            // $table->foreignId('agence_id')->nullable()->constrained('agences')->onDelete('set null');
    
            $table->rememberToken(); 
            $table->timestamps();
        });
    
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
