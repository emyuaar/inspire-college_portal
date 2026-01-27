<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use App\Models\Assignment;

class GradeSheetCell extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'grade_sheet_cells';
    protected $guarded = [];

    protected $casts = [
        'deadline_at' => 'date',
        'submitted_at' => 'datetime',
        'feedback_at' => 'datetime',
    ];

    public function row()
    {
        return $this->belongsTo(GradeSheetRow::class, 'grade_sheet_row_id');
    }

    public function attempts()
    {
        return $this->hasMany(GradeAttempt::class, 'grade_sheet_cell_id');
    }

    public function latestAttempt()
    {
        return $this->hasOne(GradeAttempt::class, 'grade_sheet_cell_id')->latestOfMany();
    }
}
