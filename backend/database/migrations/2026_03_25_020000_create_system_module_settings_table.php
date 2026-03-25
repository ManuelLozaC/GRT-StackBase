<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_module_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('module_key');
            $table->string('setting_key');
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['module_key', 'setting_key']);
            $table->foreign('module_key')
                ->references('key')
                ->on('system_modules')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_module_settings');
    }
};
