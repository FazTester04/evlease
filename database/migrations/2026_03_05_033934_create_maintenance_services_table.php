<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // On SQLite, drop the existing table created by the earlier migration so we can
        // recreate it with the updated schema expected by the application model.
        if (Schema::hasTable('maintenance_services') && DB::getDriverName() === 'sqlite') {
            Schema::drop('maintenance_services');
        }

        if (! Schema::hasTable('maintenance_services')) {
            Schema::create('maintenance_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('car_id')->constrained()->onDelete('cascade');
                $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
                $table->date('scheduled_date');
                $table->date('completed_date')->nullable();
                $table->string('status')->default('in_progress'); // in_progress, completed
                $table->text('description')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_services');
    }
};
