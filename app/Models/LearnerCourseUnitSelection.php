<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnerCourseUnitSelection extends Model
{
    protected $connection = 'mysql_portal';
    protected $guarded = [];

    protected $casts = [
        'credits_at_selection' => 'decimal:2',
        'is_locked' => 'boolean',
        'selected_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }
}
