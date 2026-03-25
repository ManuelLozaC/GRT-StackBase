<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->string('nombres', 120);
            $table->string('apellido_paterno', 120)->nullable();
            $table->string('apellido_materno', 120)->nullable();
            $table->string('documento_identidad', 60)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 180)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('sexo', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('ciudad', 120)->nullable();
            $table->string('pais', 120)->nullable();
            $table->string('foto_path', 255)->nullable();
            $table->boolean('activa')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('persona_id')->nullable()->after('organizacion_activa_id')->constrained('personas')->nullOnDelete();
            $table->string('alias', 60)->nullable()->after('name');
            $table->boolean('primer_acceso_pendiente')->default(false)->after('activo');
            $table->timestamp('expira_password_en')->nullable()->after('primer_acceso_pendiente');

            $table->unique('alias');
        });

        Schema::create('oficinas', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->string('nombre', 140);
            $table->string('slug', 160);
            $table->string('codigo', 40)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 120)->nullable();
            $table->string('pais', 120)->nullable();
            $table->boolean('activa')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organizacion_id', 'slug']);
        });

        Schema::create('divisiones', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->string('nombre', 140);
            $table->string('slug', 160);
            $table->text('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organizacion_id', 'slug']);
        });

        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisiones')->nullOnDelete();
            $table->string('nombre', 140);
            $table->string('slug', 160);
            $table->text('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organizacion_id', 'slug']);
        });

        Schema::create('cargos', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->string('nombre', 140);
            $table->string('slug', 160);
            $table->text('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organizacion_id', 'slug']);
        });

        Schema::create('asignaciones_laborales', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->cascadeOnDelete();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('oficina_id')->constrained('oficinas')->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisiones')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('cargo_id')->nullable()->constrained('cargos')->nullOnDelete();
            $table->foreignId('jefe_asignacion_id')->nullable()->constrained('asignaciones_laborales')->nullOnDelete();
            $table->foreignId('aprobador_asignacion_id')->nullable()->constrained('asignaciones_laborales')->nullOnDelete();
            $table->boolean('es_principal')->default(false);
            $table->string('estado', 30)->default('active');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->json('metadata')->nullable();
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
        Schema::dropIfExists('oficinas');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['alias']);
            $table->dropConstrainedForeignId('persona_id');
            $table->dropColumn([
                'alias',
                'primer_acceso_pendiente',
                'expira_password_en',
            ]);
        });

        Schema::dropIfExists('personas');
    }
};
