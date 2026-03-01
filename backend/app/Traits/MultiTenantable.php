<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait MultiTenantable
{
    protected static function bootMultiTenantable()
    {
        static::addGlobalScope('organizacion', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('organizacion_id', auth()->user()->organizacion_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && !$model->organizacion_id) {
                $model->organizacion_id = auth()->user()->organizacion_id;
            }
        });
    }
}
