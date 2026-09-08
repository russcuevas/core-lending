<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // specific recipient, or null for role broadcast
            $table->string('target_role')->nullable(); // host, admin_encoder, admin_releasing, collector, client
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // request_alert, approval_notice, payment_received, delinquency_alert
            $table->string('link_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
