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

            // Identité
            $table->string('reference', 5)->unique();
            $table->string('civilite', 10)->default('Autre');
            $table->string('nom', 100);
            $table->string('prenom', 150);

            // 📞 Contact
            $table->string('phone');                        // E.164 : +33612345678
            $table->string('dial_code', 5)->nullable();     // +33, +224, +262
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            // ✅ Index composite pour permettre les doublons de phone
            // mais distinguer par country_code (ex: +262 pour Réunion et Mayotte)
            $table->index(['phone', 'country_code'], 'idx_phone_country');

            // Statut
            $table->enum('statut', ['active', 'attente', 'bloque', 'archive'])
                  ->default('attente');

            // Infos personnelles
            $table->date('date_naissance')->default('9999-12-31');

            // Auth
            $table->string('password');

            // Rôle (Spatie)
            $table->foreignId('role_id')
                ->constrained('roles')
                ->restrictOnDelete()
                ->default(1);

            // 🌍 Adresse intégrée (pays obligatoire)
            $table->string('pays');                          // France, Guinée, Mayotte…
            $table->string('country_code', 2);               // ✅ ISO2 : FR, GN, YT, RE (REQUIRED)
            $table->string('adresse')->nullable();
            $table->string('complement_adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('quartier')->nullable();
            $table->string('region')->nullable();
            $table->string('code_postal')->nullable();

            // Laravel
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
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};