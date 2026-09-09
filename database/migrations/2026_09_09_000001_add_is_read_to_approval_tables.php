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
        if (Schema::hasTable('loans') && !Schema::hasColumn('loans', 'is_read')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->boolean('is_read')->default(false)->after('status');
            });
        }

        if (Schema::hasTable('wallet_transactions') && !Schema::hasColumn('wallet_transactions', 'is_read')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->boolean('is_read')->default(false)->after('status');
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_read')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_read')->default(false)->after('status');
            });
        }

        if (Schema::hasTable('client_update_requests') && !Schema::hasColumn('client_update_requests', 'is_read')) {
            Schema::table('client_update_requests', function (Blueprint $table) {
                $table->boolean('is_read')->default(false)->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('loans') && Schema::hasColumn('loans', 'is_read')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropColumn('is_read');
            });
        }

        if (Schema::hasTable('wallet_transactions') && Schema::hasColumn('wallet_transactions', 'is_read')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropColumn('is_read');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_read')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_read');
            });
        }

        if (Schema::hasTable('client_update_requests') && Schema::hasColumn('client_update_requests', 'is_read')) {
            Schema::table('client_update_requests', function (Blueprint $table) {
                $table->dropColumn('is_read');
            });
        }
    }
};
