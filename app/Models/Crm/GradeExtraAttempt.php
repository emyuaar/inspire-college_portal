<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class GradeExtraAttempt extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'grade_extra_attempts';
    protected $guarded = [];

    protected $casts = [
        'granted_at' => 'datetime',
        'additional_attempts' => 'integer',
    ];
}

