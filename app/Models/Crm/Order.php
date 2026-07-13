<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    protected $connection = 'mysql_crm';
    protected $table = 'orders';

    protected $fillable = [
        'learner_id',
        'partner_learner_id',
        'enrolment_id',
        'amount',
        'stripe_payment_id',
        'status_id',
        'payment_mode',
        'plan_deposit_amount',
        'plan_months',
        'plan_monthly_amount',
        'plan_full_amount',
        'plan_title',
        'plan_meta',
        'deposit_grace_until',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'plan_deposit_amount' => 'decimal:2',
        'plan_monthly_amount' => 'decimal:2',
        'plan_full_amount' => 'decimal:2',
        'plan_meta' => 'array',
    ];

    public function learner()
    {
        return $this->belongsTo(\App\Models\User::class, 'learner_id');
    }

    public function partnerLearner()
    {
        return $this->belongsTo(PartnerLearner::class, 'partner_learner_id');
    }

    public function enrolment()
    {
        return $this->belongsTo(Enrolment::class, 'enrolment_id');
    }

    public function installments()
    {
        return $this->hasMany(OrderInstallment::class, 'order_id');
    }
}
