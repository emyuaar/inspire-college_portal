<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseCategory extends Model
{
    use HasFactory,SoftDeletes;

    protected $connection = 'mysql_website';

    protected $table = 'course_categories';

    public function courses(): HasMany 
    {
        return $this->hasMany(Course::class,'course_category_id','id');
    }
}
