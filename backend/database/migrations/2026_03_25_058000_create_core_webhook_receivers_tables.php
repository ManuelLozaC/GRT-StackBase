<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_webhook_receivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->string('module_key', 120)->index();
            $table->string('event_key', 160)->index();
            $table->string('source_name', 160);
            $table->text('signing_secret');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('core_webhook_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('core_webhook_receivers')->nullOnDelete();
            $table->string('module_key', 120)->index();
            $table->string('event_key', 160)->index();
            $table->string('source_name', 160)->nullable();
            $table->string('signature_status', 40)->index();
            $table->string('processing_status', 40)->default('accepted')->index();
            $table->string('request_id', 64)->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_webhook_receipts');
        Schema::dropIfExists('core_webhook_receivers');
    }
};
