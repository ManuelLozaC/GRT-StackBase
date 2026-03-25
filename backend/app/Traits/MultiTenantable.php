<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
<<<<<<< HEAD
use Illuminate\Support\Facades\Schema;
use Throwable;
=======
use Illuminate\Database\Eloquent\Model;
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3

trait MultiTenantable
{
    protected static function bootMultiTenantable(): void
    {
<<<<<<< HEAD
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
=======
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
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
            }
        });
    }

<<<<<<< HEAD
    protected static function resolveCurrentOrganizationId(): ?int
    {
        if (! auth()->check()) {
            return null;
        }

        return auth()->user()?->organizacion_activa_id;
    }

    protected static function supportsOrganizationColumn(): bool
    {
        try {
            return Schema::hasColumn((new static())->getTable(), 'organizacion_id');
        } catch (Throwable) {
            return false;
        }
=======
    protected static function omitirAislamientoTenant(): bool
    {
        return property_exists(static::class, 'omitirAislamientoTenant') && static::$omitirAislamientoTenant === true;
    }

    protected static function tieneColumnaOrganizacion(): bool
    {
        return ! (property_exists(static::class, 'sinColumnaOrganizacion') && static::$sinColumnaOrganizacion === true);
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
    }
}
