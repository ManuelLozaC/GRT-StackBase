<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_error_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_id', 64)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('error_class');
            $table->string('error_code', 120)->default('internal_error')->index();
            $table->text('message')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('line_number')->nullable();
            $table->json('context')->nullable();
            $table->json('trace_excerpt')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_error_logs');
    }
};
