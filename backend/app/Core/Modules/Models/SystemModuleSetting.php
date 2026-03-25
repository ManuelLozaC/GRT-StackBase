<?php

namespace App\Core\Modules\Models;

use Illuminate\Database\Eloquent\Model;

class SystemModuleSetting extends Model
{
    protected $table = 'system_module_settings';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }
}
