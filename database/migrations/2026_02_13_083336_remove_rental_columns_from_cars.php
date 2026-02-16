<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'price_per_day',
                'mileage',
                'transmission',
                'seats',
                'fuel_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->decimal('price_per_day', 10, 2)->nullable();
            $table->integer('mileage')->nullable();
            $table->string('transmission')->nullable();
            $table->integer('seats')->nullable();
            $table->string('fuel_type')->nullable();
        });
    }
};