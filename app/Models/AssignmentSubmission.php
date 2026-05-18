<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $connection = 'mysql_portal';
    protected $table      = 'assignment_submissions';

    protected $guarded = [];

    public function files()
    {
        return $this->hasMany(AssignmentSubmissionFile::class, 'assignment_submission_id');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function learner()
    {
        return $this->belongsTo(User::class, 'learner_id');
    }
}
