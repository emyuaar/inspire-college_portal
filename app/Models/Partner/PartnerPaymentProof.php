<?php

namespace App\Models\Partner;

use App\Models\Crm\Enrolment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PartnerPaymentProof extends Model
{
    protected $connection = 'mysql_crm';

    protected $table = 'partner_payment_proofs';

    protected $fillable = [
        'partner_learner_installment_id', 'partner_id', 'learner_id', 'enrolment_id',
        'order_id', 'course_id', 'submitted_amount', 'payment_date', 'payment_reference',
        'proof_path', 'notes', 'status',
    ];

    protected $casts = [
        'submitted_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function installment()
    {
        return $this->belongsTo(PartnerLearnerInstallment::class, 'partner_learner_installment_id');
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function enrolment()
    {
        return $this->belongsTo(Enrolment::class, 'enrolment_id');
    }
}
