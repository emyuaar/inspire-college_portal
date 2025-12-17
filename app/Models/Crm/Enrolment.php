<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use App\Models\Website\Course;

class Enrolment extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'enrolments';

    protected $fillable = [
        'course_id',
        'learner_id',
        'status_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function status()
    {
        return $this->belongsTo(EnrolmentStatus::class, 'status_id');
    }
}
