<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class GradeAttempt extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'grade_attempts';
    protected $guarded = [];

    protected $casts = [
        'graded_at' => 'datetime',
    ];

    public function cell()
    {
        return $this->belongsTo(GradeSheetCell::class, 'grade_sheet_cell_id');
    }

    public function assessor()
    {
        return $this->belongsTo(CrmUser::class, 'assessor_id');
    }

    public function attachments()
    {
        return $this->hasMany(GradeAttachment::class, 'grade_attempt_id');
    }

    // Accessors for easier Blade usage
    public function getMarkingSheetPathAttribute()
    {
        return $this->attachments->where('type', 'marking_sheet')->first()?->file_path;
    }

    public function getFeedbackFilePathAttribute()
    {
        return $this->attachments->where('type', 'feedback_file')->first()?->file_path;
    }
}
