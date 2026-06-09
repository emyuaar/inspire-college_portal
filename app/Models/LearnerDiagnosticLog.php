<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnerDiagnosticLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'additional_context' => 'array',
    ];
}
