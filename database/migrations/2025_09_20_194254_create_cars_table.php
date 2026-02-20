<?php

use App\Enums\CarColor;
use App\Enums\CarStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('license_plate')->unique();
            $table->string('vin')->nullable()->unique();
            $table->string('color')->default(CarColor::WHITE->value);
            $table->text('description')->nullable();
            $table->string('status')->default(CarStatus::AVAILABLE->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};