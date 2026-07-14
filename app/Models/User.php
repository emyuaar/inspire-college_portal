<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use Notifiable;

    protected $connection = 'mysql_portal'; // Ensure this matches DB_CONNECTION in .env
    protected $table = 'users';

    protected $fillable = [
        'org_id',
        'stripe_customer_id',
        'first_name',
        'middle_name',
        'sur_name',
        'email_address',
        'password',
        'password_set_at',
        'status_id',
        'crm_approved',
        'crm_approved_at',
        'ms_user_id',
        'ms_provisioned_at',
        'ms_license_assigned',
        'ms_error_message',
    ];

    protected $casts = [
        'crm_approved' => 'boolean',
        'crm_approved_at' => 'datetime',
        'password_set_at' => 'datetime',
    ];

    public function getNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->sur_name);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Email column ka naam custom hai, helper methods:
    public function getEmailAttribute()
    {
        return $this->email_address;
    }

    // Organization relation (agar learner org se linked hai)
    public function organization()
    {
        return $this->belongsTo(User::class, 'org_id');
    }

    // Organization -> learners
    public function learners()
    {
        return $this->hasMany(User::class, 'org_id');
    }

    // Learner Enrolments (CRM linked)
    public function enrolments()
    {
        // Enrolment model in Portal namespace (App\Models\Crm\Enrolment)
        return $this->hasMany(\App\Models\Crm\Enrolment::class, 'learner_id');
    }

    // Helper: account type
    public function isOrganization(): bool
    {
        return $this->org_id === $this->id;
    }

    public function isStandaloneLearner(): bool
    {
        return $this->org_id === 0;
    }

    public function isOrgLearner(): bool
    {
        return $this->org_id > 0 && $this->org_id !== $this->id;
    }

    public function isLearner(): bool
    {
        return $this->org_id !== $this->id;
    }

    // Scopes
    public function scopePartner($query)
    {
        return $query->whereColumn('id', 'org_id');
    }

    public function scopeLearner($query)
    {
        return $query->whereColumn('id', '!=', 'org_id');
    }

    public function scopeMyLearners($query, $partnerId)
    {
        return $query->where('org_id', $partnerId)->where('id', '!=', $partnerId);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function detail()
    {
        return $this->hasOne(UserDetail::class);
    }

    /**
     * Strict Verification Check.
     * Returns true ONLY if all requirements are marked as VERIFIED in CRM.
     * Completing them is not enough.
     */
    public function isVerified(): bool
    {
        // 1. Global Check (Legacy or Manual Override)
        if (!$this->crm_approved) {
            return false;
        }

        // 2. Granular Verification Check
        $onboarding = \App\Models\Crm\LearnerOnboardingStatus::where('learner_id', $this->id)->first();

        if (!$onboarding) {
            return false;
        }

        return $onboarding->personal_verified
            && $onboarding->rpl_verified
            && $onboarding->disability_verified;
    }

    /**
     * Helper to check if requirements are completed (submitted).
     */
    public function areRequirementsMet(): bool
    {
        $onboarding = \App\Models\Crm\LearnerOnboardingStatus::where('learner_id', $this->id)->first();

        return $onboarding
            && $onboarding->personal_info_completed
            && $onboarding->rpl_info_completed
            && $onboarding->disability_info_completed;
    }
}
