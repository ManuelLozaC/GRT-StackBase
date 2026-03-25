<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\GeneraUuid;
use App\Traits\MultiTenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use GeneraUuid;
    use MultiTenantable;
    use SoftDeletes;

    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
