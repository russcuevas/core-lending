<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_update_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->json('old_data');
            $table->json('new_data');
            $table->string('status')->default('pending_host_approval'); // pending_host_approval, approved, declined
            $table->foreignId('host_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('host_approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_update_requests');
    }
};
