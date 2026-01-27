<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $connection = 'mysql_portal';        // CRM DB connection
    protected $table = 'lms_assignments';  // CRM table name

    protected $guarded = [];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function files()
    {
        return $this->hasMany(AssignmentFile::class, 'assignment_id');
    }

    public function course()
    {
        return $this->belongsTo(\App\Models\Website\Course::class, 'course_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }

    public function gradeResets()
    {
        return $this->setConnection('mysql_crm')->hasMany(\App\Models\Crm\GradeReset::class, 'portal_assignment_id');
    }
}
