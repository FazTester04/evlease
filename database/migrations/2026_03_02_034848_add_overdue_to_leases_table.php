<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('leases', function (Blueprint $table) {
        $table->boolean('overdue')->default(false)->after('status');
    });
}

public function down()
{
    Schema::table('leases', function (Blueprint $table) {
        $table->dropColumn('overdue');
    });
}
};
