<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::table('cars', function (Blueprint $table) {
        $table->string('insurance_provider')->nullable()->after('status');
        $table->string('insurance_policy_number')->nullable()->after('insurance_provider');
        $table->date('insurance_expiry_date')->nullable()->after('insurance_policy_number');
        $table->string('road_tax_number')->nullable()->after('insurance_expiry_date');
        $table->date('road_tax_expiry_date')->nullable()->after('road_tax_number');
    });
}

public function down()
{
    Schema::table('cars', function (Blueprint $table) {
        $table->dropColumn([
            'insurance_provider',
            'insurance_policy_number',
            'insurance_expiry_date',
            'road_tax_number',
            'road_tax_expiry_date',
        ]);
    });
}
};
