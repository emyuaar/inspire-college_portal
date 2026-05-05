<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    protected $connection = 'mysql_portal';
    protected $table = 'lms_course_modules';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
    ];

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'module_id')->orderBy('sort_order');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'module_id')->orderBy('id');
    }
}