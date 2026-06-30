<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('company_name');
            $table->string('status')->default('inactive');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });

        Schema::create('tenant_salestrail_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('api_url')->nullable();
            $table->string('api_key');
            $table->string('webhook_secret')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_bitrix_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('bitrix_domain');
            $table->text('webhook_url')->nullable();
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('external_line_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_users_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('salestrail_user_id');
            $table->string('salestrail_email');
            $table->string('employee_phone');
            $table->string('bitrix_user_id');
            $table->timestamps();

            $table->unique(['tenant_id', 'salestrail_user_id']);
        });

        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('salestrail_call_id');
            $table->string('bitrix_call_id')->nullable();
            $table->string('employee_phone');
            $table->string('customer_phone');
            $table->boolean('inbound');
            $table->boolean('answered');
            $table->integer('duration')->default(0);
            $table->text('recording_url')->nullable();
            $table->boolean('recording_uploaded')->default(false);
            $table->boolean('synced_to_bitrix')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'salestrail_call_id']);
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('call_id')->nullable();
            $table->string('action');
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->string('status');
            $table->integer('retries')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('calls');
        Schema::dropIfExists('tenant_users_mapping');
        Schema::dropIfExists('tenant_bitrix_accounts');
        Schema::dropIfExists('tenant_salestrail_accounts');
        Schema::dropIfExists('tenants');
    }
};
