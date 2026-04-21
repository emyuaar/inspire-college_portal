<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnerDiagnosticLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'validation_errors' => 'array',
        'additional_context' => 'array',
        'learner_initiated' => 'boolean',
        'blocked_by_permission' => 'boolean',
        'is_reproducible' => 'boolean',
        'retry_happened' => 'boolean',
        'elapsed_time_ms' => 'decimal:2',
        'upload_duration_ms' => 'decimal:2',
    ];
}
