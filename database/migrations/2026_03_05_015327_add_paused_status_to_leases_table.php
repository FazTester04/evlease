<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE leases MODIFY COLUMN status ENUM('active', 'pending', 'ended', 'paused') NOT NULL DEFAULT 'active'");
        }
    }

    public function down()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE leases MODIFY COLUMN status ENUM('active', 'pending', 'ended') NOT NULL DEFAULT 'active'");
        }
    }
};