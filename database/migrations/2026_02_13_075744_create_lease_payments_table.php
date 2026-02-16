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
    Schema::create('lease_payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lease_id')->constrained()->onDelete('cascade');
        $table->date('due_date');
        $table->date('paid_date')->nullable();
        $table->decimal('amount', 10, 2);
        $table->enum('status', ['paid', 'pending', 'overdue'])->default('pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_payments');
    }
};
