<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 40);
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('setting_key', 120);
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'organizacion_id', 'user_id', 'setting_key'], 'core_settings_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_settings');
    }
};
