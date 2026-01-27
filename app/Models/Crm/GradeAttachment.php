<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class GradeAttachment extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'grade_attachments';
    protected $guarded = [];

    public function attempt()
    {
        return $this->belongsTo(GradeAttempt::class, 'grade_attempt_id');
    }
}
