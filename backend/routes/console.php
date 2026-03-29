<?php

use App\Core\Settings\Models\CoreSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('settings:deduplicate', function () {
    $duplicates = CoreSetting::query()
        ->select('scope', 'organizacion_id', 'user_id', 'setting_key', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
        ->groupBy('scope', 'organizacion_id', 'user_id', 'setting_key')
        ->having('total', '>', 1)
        ->get();

    $deleted = 0;

    foreach ($duplicates as $duplicate) {
        $query = CoreSetting::query()
            ->where('scope', $duplicate->scope)
            ->where('setting_key', $duplicate->setting_key)
            ->where('id', '!=', $duplicate->keep_id);

        $duplicate->organizacion_id === null
            ? $query->whereNull('organizacion_id')
            : $query->where('organizacion_id', $duplicate->organizacion_id);

        $duplicate->user_id === null
            ? $query->whereNull('user_id')
            : $query->where('user_id', $duplicate->user_id);

        $deleted += $query->delete();
    }

    $this->info("Settings duplicados eliminados: {$deleted}");
})->purpose('Remove duplicated core settings while keeping the newest value');
