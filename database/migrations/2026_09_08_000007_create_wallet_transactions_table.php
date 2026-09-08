<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // cash_in, cash_out, collector_cashout, savings_deposit, daily_interest, loan_release, loan_payment
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending_releasing_review'); 
            // pending_releasing_review, pending_host_approval, approved_by_host, completed, declined
            $table->foreignId('releasing_officer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('releasing_notes')->nullable();
            $table->date('releasing_scheduled_date')->nullable();
            $table->foreignId('host_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('host_approved_at')->nullable();
            $table->text('host_notes')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('proof_image_path')->nullable();
            $table->boolean('pin_verified')->default(false);
            $table->decimal('user_balance_after', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
