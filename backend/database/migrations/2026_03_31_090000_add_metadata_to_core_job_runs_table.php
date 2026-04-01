<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_job_runs', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('result_payload');
        });
    }

    public function down(): void
    {
        Schema::table('core_job_runs', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
