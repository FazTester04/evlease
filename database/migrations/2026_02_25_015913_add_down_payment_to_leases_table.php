<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('leases', function (Blueprint $table) {
        $table->decimal('down_payment', 10, 2)->default(0)->after('monthly_payment');
    });
}

public function down()
{
    Schema::table('leases', function (Blueprint $table) {
        $table->dropColumn('down_payment');
    });
}
};
