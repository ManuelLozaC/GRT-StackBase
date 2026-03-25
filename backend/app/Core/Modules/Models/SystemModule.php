<?php

namespace App\Core\Modules\Models;

use Illuminate\Database\Eloquent\Model;

class SystemModule extends Model
{
    protected $table = 'system_modules';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function toRegistryArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'provider' => $this->provider,
            'enabled' => (bool) $this->is_enabled,
            'is_demo' => (bool) $this->is_demo,
        ];
    }
}
