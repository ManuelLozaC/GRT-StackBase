<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_contacts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones');
            $table->string('nombre', 120);
            $table->string('email', 180)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('empresa', 140)->nullable();
            $table->string('estado', 30);
            $table->string('prioridad', 30);
            $table->text('notas')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organizacion_id', 'estado']);
            $table->index(['organizacion_id', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_contacts');
    }
};
