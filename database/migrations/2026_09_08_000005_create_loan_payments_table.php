<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('collector_id')->nullable()->constrained('collectors')->onDelete('set null');
            $table->decimal('amount_paid', 12, 2);
            $table->string('proof_image_path')->nullable();
            $table->boolean('client_pin_verified')->default(true);
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->decimal('client_remaining_balance_after', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
