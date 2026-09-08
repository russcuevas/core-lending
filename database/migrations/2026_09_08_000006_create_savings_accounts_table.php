<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('collector_id')->nullable()->constrained('collectors')->onDelete('set null');
            $table->decimal('deposit_amount', 12, 2);
            $table->decimal('interest_rate_percent', 5, 2)->default(10.00); // 10%
            $table->integer('lock_in_days')->default(60);
            $table->decimal('daily_interest_amount', 12, 4); // (deposit * 10%) / 60
            $table->decimal('total_expected_interest', 12, 2); // deposit * 10%
            $table->decimal('accumulated_interest_paid', 12, 2)->default(0.00);
            $table->integer('days_credited')->default(0);
            $table->date('start_date');
            $table->date('maturity_date');
            $table->string('status')->default('active'); // active, matured, completed
            $table->decimal('collector_commission_amount', 12, 2)->default(0.00); // 5% of deposit
            $table->boolean('collector_commission_credited')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_accounts');
    }
};
