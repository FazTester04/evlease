<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_payments', function (Blueprint $table) {
            // 1. Add missing columns
            if (!Schema::hasColumn('lease_payments', 'driver_id')) {
                $table->foreignId('driver_id')->after('lease_id')->constrained('users');
            }
            
            if (!Schema::hasColumn('lease_payments', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('status');
            }

            // 2. Ensure data types are correct (requires 'composer require doctrine/dbal')
            $table->decimal('amount', 10, 2)->change();
            $table->date('due_date')->nullable()->change();
            $table->date('paid_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lease_payments', function (Blueprint $table) {
            $table->dropColumn(['driver_id', 'receipt_path']);
        });
    }
};