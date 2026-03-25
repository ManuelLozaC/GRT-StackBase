<?php

namespace App\Core\Settings\Models;

use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreSetting extends Model
{
    protected $table = 'core_settings';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class, 'organizacion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
