<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_vault_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // in (cash received), out (cash released)
            $table->string('category'); // cash_in, cash_out, loan_release, loan_repayment, expense, savings_deposit, initial_capital
            $table->decimal('amount', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('vault_balance_after', 12, 2)->default(0.00);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_vault_ledgers');
    }
};
