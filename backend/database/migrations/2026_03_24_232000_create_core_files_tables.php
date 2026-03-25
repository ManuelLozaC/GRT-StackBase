<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name');
            $table->string('extension', 20)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('visibility', 20)->default('private');
            $table->unsignedInteger('version')->default(1);
            $table->string('security_token', 20)->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('core_file_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_file_id')->constrained('core_files')->cascadeOnDelete();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 30);
            $table->string('status', 30)->default('completed');
            $table->timestamp('downloaded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_file_downloads');
        Schema::dropIfExists('core_files');
    }
};
