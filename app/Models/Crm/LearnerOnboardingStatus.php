<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class LearnerOnboardingStatus extends Model
{
    protected $connection = 'mysql_crm';

    protected $table = 'learner_onboarding_statuses';

    protected $fillable = [
        'learner_id',

        'personal_info_completed',
        'rpl_info_completed',
        'disability_info_completed',

        'personal_verified',
        'rpl_verified',
        'disability_verified',
    ];

    protected $casts = [
        'personal_info_completed' => 'boolean',
        'rpl_info_completed'      => 'boolean',
        'disability_info_completed'=> 'boolean',
    ];

    public function getAllCompletedAttribute(): bool
    {
        return $this->personal_info_completed
            && $this->rpl_info_completed
            && $this->disability_info_completed;
    }
}