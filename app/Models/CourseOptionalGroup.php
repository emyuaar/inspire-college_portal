<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseOptionalGroup extends Model
{
    protected $connection = 'mysql_portal';
    protected $guarded = [];

    protected $casts = [
        'minimum_units' => 'integer',
        'maximum_units' => 'integer',
        'minimum_credits' => 'decimal:2',
        'maximum_credits' => 'decimal:2',
    ];

    public function modules()
    {
        return $this->hasMany(CourseModule::class, 'optional_group_id');
    }
}
