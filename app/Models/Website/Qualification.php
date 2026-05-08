<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    protected $connection = 'mysql_website';
    protected $table = 'qualifications';
    protected $guarded = [];

    public function courses()
    {
        return $this->hasMany(Course::class, 'qualification_id');
    }
}
