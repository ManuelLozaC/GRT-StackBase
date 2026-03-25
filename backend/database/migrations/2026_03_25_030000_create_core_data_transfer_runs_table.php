<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_data_transfer_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resource_key', 120);
            $table->string('source_module', 120)->nullable();
            $table->string('type', 20);
            $table->string('status', 40);
            $table->string('file_name', 180)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('records_total')->default(0);
            $table->unsignedInteger('records_processed')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->text('error_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['organizacion_id', 'resource_key']);
            $table->index(['resource_key', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_data_transfer_runs');
    }
};
