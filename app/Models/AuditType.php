<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditType extends Model
{
    use SoftDeletes;

    protected $table = 'audit_types';

    protected $guarded = [];
}
