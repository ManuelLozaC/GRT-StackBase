<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Str;

trait GeneraUuid
{
    protected static function bootGeneraUuid(): void
    {
        static::creating(static function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
