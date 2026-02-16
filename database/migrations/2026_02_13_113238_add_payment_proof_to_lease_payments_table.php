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
    Schema::table('lease_payments', function (Blueprint $table) {
        $table->string('proof_path')->nullable()->after('status');
        $table->timestamp('proof_uploaded_at')->nullable()->after('proof_path');
        $table->timestamp('verified_at')->nullable()->after('proof_uploaded_at');
        $table->foreignId('verified_by')->nullable()->constrained('users')->after('verified_at');
    });
}

public function down()
{
    Schema::table('lease_payments', function (Blueprint $table) {
        $table->dropForeign(['verified_by']);
        $table->dropColumn(['proof_path', 'proof_uploaded_at', 'verified_at', 'verified_by']);
    });
}

};
