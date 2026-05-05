<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class GradeSheetRow extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'grade_sheet_rows';
    protected $guarded = [];

    // Helper to filter by learner
    public function scopeForLearner($query, $learnerId)
    {
        return $query->where('learner_id', $learnerId);
    }
}
