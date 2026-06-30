<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_salestrail_accounts', function (Blueprint $table) {
            $table->string('user')->nullable()->after('webhook_secret');
            $table->string('password')->nullable()->after('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_salestrail_accounts', function (Blueprint $table) {
            $table->dropColumn(['user', 'password']);
        });
    }
};
