<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnerCourseUnitSelectionHistory extends Model
{
    protected $connection = 'mysql_portal';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['old_value' => 'array', 'new_value' => 'array', 'created_at' => 'datetime'];
}
