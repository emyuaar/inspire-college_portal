<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    // CRM database connection
    protected $connection = 'mysql_portal';

    // CRM table name
    protected $table = 'lms_lessons';

    protected $guarded = [];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }
}
