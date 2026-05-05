<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class PartnerCoursePaymentPlan extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'partner_course_payment_plans';

    protected $fillable = [
        'pac_id',
        'plan_type',
        'amount',
        'deposit',
        'months',
        'monthly_amount',
        'currency',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'amount' => 'decimal:2',
        'deposit' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
    ];
}
