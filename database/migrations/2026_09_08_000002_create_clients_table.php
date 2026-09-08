<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('collector_id')->nullable()->constrained('collectors')->onDelete('set null');
            $table->string('qr_code_token')->unique();
            $table->string('qr_code_path')->nullable();
            $table->decimal('wallet_balance', 12, 2)->default(0.00);
            $table->unsignedBigInteger('current_loan_id')->nullable();
            $table->string('status')->default('pending_host_approval'); // pending_host_approval, active, delinquent, completed, rejected
            $table->date('last_payment_date')->nullable();
            $table->integer('consecutive_missed_days')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
