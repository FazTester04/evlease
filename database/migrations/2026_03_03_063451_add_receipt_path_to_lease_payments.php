<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lease_payments', function (Blueprint $table) {
            // Add receipt_path column
            $table->string('receipt_path')->nullable()->after('status');
            
            // Optionally drop payment_method if it exists (adjust if you already have it)
            if (Schema::hasColumn('lease_payments', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }

    public function down()
    {
        Schema::table('lease_payments', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });
    }
};