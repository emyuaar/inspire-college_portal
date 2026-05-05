<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class PartnerAssignedCourse extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'partner_assigned_courses';

    protected $fillable = [
        'partner_id',
        'course_id',
        'notes',
    ];

    public function plans()
    {
        return $this->hasMany(PartnerCoursePaymentPlan::class, 'pac_id');
    }

    public function course()
    {
        // Link to Website Course (Website DB)
        return $this->belongsTo(\App\Models\Website\Course::class, 'course_id');
    }
}
