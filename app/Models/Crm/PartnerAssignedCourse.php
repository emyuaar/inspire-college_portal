<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class PartnerAssignedCourse extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'partner_assigned_courses';

    protected $fillable = [
        'partner_id',
        'course_id',
        'discount_type',
        'discount_value',
        'allow_full_payment',
        'allow_three_months',
        'allow_installments',
        'status',
        'notes',
    ];

    public function installmentPlans()
    {
        return $this->hasMany(PartnerInstallmentPlan::class, 'partner_id', 'partner_id')
                    ->whereColumn('course_id', 'course_id');
    }

    public function plans()
    {
        return $this->hasMany(PartnerCoursePaymentPlan::class, 'pac_id');
    }

    public function course()
    {
        // Link to Website Course (Website DB)
        return $this->belongsTo(\App\Models\Website\Course::class, 'course_id');
    }
}
