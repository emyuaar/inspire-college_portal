<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerLearner extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'partner_learners';

    protected $fillable = [
        'user_id',
        'partner_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'personal_email',
        'phone',
        'dob',
        'gender',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'zip_code',
        'course_id',
        'selected_plan_id',
        'payment_mode',
        'payment_status',
        'activation_status',
        'enrolment_status',
        'account_status',
        'created_by_partner_id',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    // Compatibility with User model views
    public function getSurNameAttribute()
    {
        return $this->last_name;
    }

    public function getNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function partner()
    {
        return $this->belongsTo(\App\Models\User::class, 'partner_id');
    }

    public function enrolments()
    {
        return $this->hasMany(\App\Models\Crm\Enrolment::class, 'partner_learner_id');
    }
}
