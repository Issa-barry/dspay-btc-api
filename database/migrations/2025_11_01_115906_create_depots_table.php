<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('serviceId');
            $table->decimal('montant_envoye', 15, 2);
            $table->unsignedBigInteger('amount');
            $table->string('recipientTel')->nullable();        // ✅ facultatif
            $table->string('accountId')->nullable();           // ✅ facultatif
            $table->string('customerPhoneNumber');
            $table->string('fieldName');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('transaction_ref')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depots');
    }
};
