<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->string('codigo_iso2', 2)->nullable()->unique();
            $table->string('codigo_iso3', 3)->nullable()->unique();
            $table->string('gentilicio')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ciudades', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pais_id')->constrained('paises');
            $table->string('nombre');
            $table->string('codigo', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('organizaciones', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombre');
            $table->string('nombre_comercial')->nullable();
            $table->string('nit', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion')->nullable();
            $table->foreignId('pais_id')->nullable()->constrained('paises');
            $table->foreignId('ciudad_id')->nullable()->constrained('ciudades');
            $table->boolean('activa')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oficinas', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->constrained('organizaciones');
            $table->foreignId('ciudad_id')->nullable()->constrained('ciudades');
            $table->string('nombre');
            $table->string('codigo', 50)->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->boolean('activa')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('personas', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones');
            $table->foreignId('ciudad_id')->nullable()->constrained('ciudades');
            $table->string('tipo_documento', 50)->nullable();
            $table->string('numero_documento', 50)->nullable();
            $table->string('nombres');
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('genero', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion')->nullable();
            $table->string('foto_path')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones');
            $table->foreignId('persona_id')->nullable()->constrained('personas');
            $table->string('alias')->unique();
            $table->string('nombre_mostrar');
            $table->string('email')->unique();
            $table->string('telefono', 30)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('es_superusuario')->default(false);
            $table->boolean('debe_cambiar_password')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('personas');
        Schema::dropIfExists('oficinas');
        Schema::dropIfExists('organizaciones');
        Schema::dropIfExists('ciudades');
        Schema::dropIfExists('paises');
    }
};
