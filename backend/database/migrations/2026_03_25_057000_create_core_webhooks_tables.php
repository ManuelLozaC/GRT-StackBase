<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->string('module_key', 120)->index();
            $table->string('event_key', 160)->index();
            $table->string('target_url');
            $table->text('signing_secret');
            $table->json('custom_headers')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('core_webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained('core_webhook_endpoints')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module_key', 120)->index();
            $table->string('event_key', 160)->index();
            $table->string('target_url');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->string('status', 40)->index();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->string('request_id', 64)->nullable()->index();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_webhook_deliveries');
        Schema::dropIfExists('core_webhook_endpoints');
    }
};
