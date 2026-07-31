<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class EnrolmentPricingSnapshot extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'enrolment_pricing_snapshots';

    protected $fillable = [
        'learner_id',
        'enrolment_id',
        'order_id',
        'course_id',
        'pricing_plan_id',
        'pricing_plan_version',
        'snapshot_json',
        'regular_fee',
        'discounted_fee',
        'discount_amount',
        'discount_percentage',
        'initial_deposit',
        'installment_months',
        'installment_amount',
        'total_payable',
        'selected_plan_name',
        'selected_plan_type',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
        'regular_fee' => 'decimal:2',
        'discounted_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'initial_deposit' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'total_payable' => 'decimal:2',
    ];
}
