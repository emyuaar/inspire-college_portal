<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    // WEBSITE database
    protected $connection = 'mysql_website';

    protected $table = 'courses';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'overview',
        'modules',
        'requirements',
        'assessment',
        'qualification',
        'career',
        'image',
        'regular_price',
        'sale_price',
        'deposit',
        'number_of_months',
        'monthly_installment',
        'qualification_id',
        'course_category_id',
        'status',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function enrolments()
    {
        return $this->hasMany(\App\Models\Crm\Enrolment::class, 'course_id');
    }

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }
}
