<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class GradeReset extends Model
{
    protected $connection = 'mysql_crm'; // Assuming this based on other files, will verify
    protected $table = 'grade_resets';
    protected $guarded = [];

    protected $casts = [
        'reset_at' => 'datetime',
    ];
}
