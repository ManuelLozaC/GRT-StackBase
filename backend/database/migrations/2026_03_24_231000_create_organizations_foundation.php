<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('organizacion_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organizacion_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organizacion_activa_id')
                ->nullable()
                ->after('password')
                ->constrained('organizaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizacion_activa_id');
        });

        Schema::dropIfExists('organizacion_user');
        Schema::dropIfExists('organizaciones');
    }
};
