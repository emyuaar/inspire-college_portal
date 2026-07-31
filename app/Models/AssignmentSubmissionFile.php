<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmissionFile extends Model
{
    protected $connection = 'mysql_portal';
    protected $table = 'assignment_submission_files';

    protected $guarded = [];

    public function submission()
    {
        return $this->belongsTo(AssignmentSubmission::class, 'assignment_submission_id');
    }
}
