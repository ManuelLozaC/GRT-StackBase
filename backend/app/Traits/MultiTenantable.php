<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait MultiTenantable
{
    protected static function bootMultiTenantable(): void
    {
        static::addGlobalScope('organizacion', static function (Builder $builder): void {
            $usuario = auth()->user();

            if (! $usuario || ! method_exists($usuario, 'esSuperusuario') || $usuario->esSuperusuario()) {
                return;
            }

            if (static::omitirAislamientoTenant() || ! static::tieneColumnaOrganizacion()) {
                return;
            }

            if ($usuario->organizacion_id !== null) {
                $builder->where($builder->qualifyColumn('organizacion_id'), $usuario->organizacion_id);
            }
        });

        static::creating(static function (Model $model): void {
            $usuario = auth()->user();

            if (! $usuario) {
                return;
            }

            if (static::tieneColumnaOrganizacion() && empty($model->getAttribute('organizacion_id')) && ! $usuario->esSuperusuario()) {
                $model->setAttribute('organizacion_id', $usuario->organizacion_id);
            }

            if ($model->getAttribute('created_by') === null) {
                $model->setAttribute('created_by', $usuario->id);
            }

            if ($model->getAttribute('updated_by') === null) {
                $model->setAttribute('updated_by', $usuario->id);
            }
        });

        static::updating(static function (Model $model): void {
            $usuario = auth()->user();

            if ($usuario && $model->getAttribute('updated_by') === null) {
                $model->setAttribute('updated_by', $usuario->id);
            }
        });
    }

    protected static function omitirAislamientoTenant(): bool
    {
        return property_exists(static::class, 'omitirAislamientoTenant') && static::$omitirAislamientoTenant === true;
    }

    protected static function tieneColumnaOrganizacion(): bool
    {
        return ! (property_exists(static::class, 'sinColumnaOrganizacion') && static::$sinColumnaOrganizacion === true);
    }
}
