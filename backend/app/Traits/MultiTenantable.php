<?php

namespace App\Traits;

use App\Core\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Throwable;

trait MultiTenantable
{
    protected static function bootMultiTenantable()
    {
        static::addGlobalScope('organizacion', function (Builder $builder) {
            $organizacionId = static::resolveCurrentOrganizationId();

            if ($organizacionId !== null && static::supportsOrganizationColumn()) {
                $builder->where('organizacion_id', $organizacionId);
            }
        });

        static::creating(function ($model) {
            $organizacionId = static::resolveCurrentOrganizationId();

            if ($organizacionId !== null && static::supportsOrganizationColumn() && ! $model->organizacion_id) {
                $model->organizacion_id = $organizacionId;
            }
        });
    }

    protected static function resolveCurrentOrganizationId(): ?int
    {
        return app(TenantContext::class)->organizationId(auth()->user());
    }

    protected static function supportsOrganizationColumn(): bool
    {
        try {
            return Schema::hasColumn((new static())->getTable(), 'organizacion_id');
        } catch (Throwable) {
            return false;
        }
    }
}
