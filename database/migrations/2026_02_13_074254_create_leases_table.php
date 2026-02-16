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
    Schema::create('leases', function (Blueprint $table) {
        $table->id();
        $table->foreignId('car_id')->constrained('cars')->onDelete('cascade'); // references cars.id
        $table->foreignId('driver_id')->constrained('users')->onDelete('cascade'); // references users.id
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->decimal('monthly_payment', 10, 2);
        $table->enum('status', ['active', 'ended', 'pending'])->default('active');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
