<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_files', function (Blueprint $table): void {
            $table->string('attached_resource_key', 120)->nullable()->after('visibility');
            $table->unsignedBigInteger('attached_record_id')->nullable()->after('attached_resource_key');
            $table->string('attached_record_label', 180)->nullable()->after('attached_record_id');
            $table->index(['attached_resource_key', 'attached_record_id'], 'core_files_attached_resource_index');
        });
    }

    public function down(): void
    {
        Schema::table('core_files', function (Blueprint $table): void {
            $table->dropIndex('core_files_attached_resource_index');
            $table->dropColumn([
                'attached_resource_key',
                'attached_record_id',
                'attached_record_label',
            ]);
        });
    }
};
