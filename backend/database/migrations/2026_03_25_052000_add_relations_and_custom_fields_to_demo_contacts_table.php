<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_contacts', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('empresa')->constrained('empresas')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->after('empresa_id')->constrained('sucursales')->nullOnDelete();
            $table->foreignId('equipo_id')->nullable()->after('sucursal_id')->constrained('equipos')->nullOnDelete();
            $table->json('custom_fields')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('demo_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipo_id');
            $table->dropConstrainedForeignId('sucursal_id');
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropColumn('custom_fields');
        });
    }
};
