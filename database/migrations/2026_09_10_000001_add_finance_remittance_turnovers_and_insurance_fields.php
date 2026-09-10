<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create cash_turnovers table first
        if (!Schema::hasTable('cash_turnovers')) {
            Schema::create('cash_turnovers', function (Blueprint $table) {
                $table->id();
                $table->string('turnover_reference')->unique();
                $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('host_id')->nullable()->constrained('users')->onDelete('set null');
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->decimal('loan_collection_amount', 12, 2)->default(0.00);
                $table->decimal('insurance_collection_amount', 12, 2)->default(0.00);
                $table->date('date');
                $table->string('status')->default('pending_host_approval'); // pending_host_approval, approved, declined
                $table->text('admin_notes')->nullable();
                $table->text('host_notes')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }

        // 2. Add fields to loan_payments table
        Schema::table('loan_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('loan_payments', 'status')) {
                $table->string('status')->default('paid')->after('client_remaining_balance_after'); // 'processing', 'paid', 'declined'
            }
            if (!Schema::hasColumn('loan_payments', 'payment_channel')) {
                $table->string('payment_channel')->default('cash_collector')->after('status'); // 'cash_collector', 'wallet_auto_deduct'
            }
            if (!Schema::hasColumn('loan_payments', 'loan_premium_amount')) {
                $table->decimal('loan_premium_amount', 12, 2)->default(0.00)->after('payment_channel');
            }
            if (!Schema::hasColumn('loan_payments', 'insurance_premium_amount')) {
                $table->decimal('insurance_premium_amount', 12, 2)->default(0.00)->after('loan_premium_amount');
            }
            if (!Schema::hasColumn('loan_payments', 'remitted_at')) {
                $table->timestamp('remitted_at')->nullable()->after('insurance_premium_amount');
            }
            if (!Schema::hasColumn('loan_payments', 'admin_pin_verified_by')) {
                $table->foreignId('admin_pin_verified_by')->nullable()->after('remitted_at')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('loan_payments', 'admin_pin_verified_at')) {
                $table->timestamp('admin_pin_verified_at')->nullable()->after('admin_pin_verified_by');
            }
            if (!Schema::hasColumn('loan_payments', 'turnover_id')) {
                $table->foreignId('turnover_id')->nullable()->after('admin_pin_verified_at')->constrained('cash_turnovers')->onDelete('set null');
            }
        });

        // 3. Add fields to loans table
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'loan_premium_daily')) {
                $table->decimal('loan_premium_daily', 12, 2)->default(0.00)->after('daily_installment');
            }
            if (!Schema::hasColumn('loans', 'insurance_premium_daily')) {
                $table->decimal('insurance_premium_daily', 12, 2)->default(0.00)->after('loan_premium_daily');
            }
            if (!Schema::hasColumn('loans', 'total_daily_payable')) {
                $table->decimal('total_daily_payable', 12, 2)->default(0.00)->after('insurance_premium_daily');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['loan_premium_daily', 'insurance_premium_daily', 'total_daily_payable']);
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropForeign(['admin_pin_verified_by']);
            $table->dropForeign(['turnover_id']);
            $table->dropColumn([
                'status',
                'payment_channel',
                'loan_premium_amount',
                'insurance_premium_amount',
                'remitted_at',
                'admin_pin_verified_by',
                'admin_pin_verified_at',
                'turnover_id',
            ]);
        });

        Schema::dropIfExists('cash_turnovers');
    }
};
