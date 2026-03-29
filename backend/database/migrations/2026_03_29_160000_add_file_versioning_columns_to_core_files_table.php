<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_files', function (Blueprint $table) {
            $table->uuid('version_group_uuid')->nullable()->after('uuid');
            $table->foreignId('previous_version_id')->nullable()->after('attached_record_label')->constrained('core_files')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable()->after('version');
        });
    }

    public function down(): void
    {
        Schema::table('core_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('previous_version_id');
            $table->dropColumn(['version_group_uuid', 'superseded_at']);
        });
    }
};
