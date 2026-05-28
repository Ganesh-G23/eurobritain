<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateType extends Model
{
    use SoftDeletes;

    protected $table = 'certificate_types';

    protected $guarded = [];

    protected $casts = [
        'types' => 'array',
    ];
}
