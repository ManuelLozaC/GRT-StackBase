<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisiones', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones');
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones');
            $table->foreignId('division_id')->nullable()->constrained('divisiones');
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cargos', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones');
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('es_aprobador')->default(false);
            $table->boolean('activa')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asignaciones_laborales', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones');
            $table->foreignId('persona_id')->constrained('personas');
            $table->foreignId('usuario_id')->nullable()->constrained('users');
            $table->foreignId('oficina_id')->constrained('oficinas');
            $table->foreignId('division_id')->nullable()->constrained('divisiones');
            $table->foreignId('area_id')->nullable()->constrained('areas');
            $table->foreignId('cargo_id')->nullable()->constrained('cargos');
            $table->foreignId('jefe_asignacion_laboral_id')->nullable()->constrained('asignaciones_laborales');
            $table->foreignId('aprobador_asignacion_laboral_id')->nullable()->constrained('asignaciones_laborales');
            $table->boolean('es_principal')->default(false);
            $table->boolean('activa')->default(true);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_laborales');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('divisiones');
    }
};
