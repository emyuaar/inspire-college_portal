<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentFile extends Model
{
    protected $connection = 'mysql_portal';
    protected $table = 'assignment_files';

    protected $guarded = [];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }
}
