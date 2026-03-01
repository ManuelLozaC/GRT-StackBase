<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\MultiTenantable;

abstract class BaseModel extends Model
{
    use SoftDeletes, MultiTenantable;

    protected $guarded = ['id', 'created_at', 'updated_at'];
}
