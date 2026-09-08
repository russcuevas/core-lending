<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('collector_id')->nullable()->constrained('collectors')->onDelete('set null');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_rate_percent', 5, 2)->default(10.00); // manual input
            $table->decimal('total_payable', 12, 2);
            $table->decimal('daily_installment', 12, 2);
            $table->integer('term_days')->default(60);
            $table->decimal('remaining_balance', 12, 2);
            $table->decimal('total_paid', 12, 2)->default(0.00);
            $table->string('status')->default('pending_host_approval'); 
            // pending_host_approval, approved_for_release, releasing_in_process, active, fully_paid, defaulted, rejected
            $table->date('release_date')->nullable();
            $table->text('release_note')->nullable();
            $table->text('decline_reason')->nullable();
            $table->foreignId('encoder_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('releasing_officer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('host_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('host_approved_at')->nullable();
            $table->string('disbursement_proof_path')->nullable();
            $table->boolean('collector_commission_paid')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
