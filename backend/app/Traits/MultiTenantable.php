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
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = static::resolveCurrentCompanyId();

            if ($companyId !== null && static::supportsCompanyColumn()) {
                $builder->where(static::companyColumnName(), $companyId);
            }
        });

        static::creating(function ($model) {
            $companyId = static::resolveCurrentCompanyId();

            if ($companyId !== null && static::supportsCompanyColumn() && ! $model->{static::companyColumnName()}) {
                $model->{static::companyColumnName()} = $companyId;
            }
        });
    }

    protected static function resolveCurrentCompanyId(): ?int
    {
        return app(TenantContext::class)->companyId(auth()->user());
    }

    protected static function supportsCompanyColumn(): bool
    {
        try {
            return Schema::hasColumn((new static())->getTable(), static::companyColumnName());
        } catch (Throwable) {
            return false;
        }
    }

    protected static function companyColumnName(): string
    {
        return 'organizacion_id';
    }
}
