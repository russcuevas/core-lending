<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // recorded by admin/host
            $table->date('date');
            $table->string('particulars');
            $table->decimal('amount', 12, 2);
            $table->string('category')->default('Office Expense'); // Utilities, Office Supplies, Transportation, Salary, Miscellaneous
            $table->string('receipt_image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
