<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            // Add lease_id column, nullable, foreign key to leases table
            $table->foreignId('lease_id')
                ->nullable()
                ->after('driver_id')  // or wherever you prefer
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['lease_id']);
            $table->dropColumn('lease_id');
        });
    }
};