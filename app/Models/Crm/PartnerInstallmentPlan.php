<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class PartnerInstallmentPlan extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'partner_installment_plans';

    protected $fillable = [
        'partner_id',
        'course_id',
        'plan_name',
        'deposit_amount',
        'installment_count',
        'installment_amount',
        'total_amount',
        'grace_days',
        'status',
    ];
}
