<?php

namespace App\Models\Partner;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Crm\Enrolment;
use App\Models\Crm\Order;
use App\Models\Website\Course;

class PartnerLearnerInstallment extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'partner_learner_installments';

    protected $fillable = [
        'partner_id',
        'learner_id',
        'enrolment_id',
        'order_id',
        'course_id',
        'plan_type',
        'total_amount',
        'deposit_amount',
        'installment_amount',
        'installments_count',
        'installment_no',
        'due_date',
        'status',
        'paid_amount',
        'paid_at',
        'payment_reference',
        'stripe_payment_intent_id',
        'receipt_path',
        'notes',
        'submitted_amount',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'submitted_amount' => 'decimal:2',
    ];

    /**
     * Get the urgency status of the installment
     * Returns: 'overdue', 'due_soon', or 'normal'
     */
    public function getUrgencyAttribute()
    {
        if ($this->status === 'paid') {
            return 'paid';
        }

        $today = now()->startOfDay();
        $nextWeek = now()->addDays(7)->endOfDay();

        if ($this->due_date->lt($today)) {
            return 'overdue';
        }

        if ($this->due_date->isBetween($today, $nextWeek)) {
            return 'due_soon';
        }

        return 'normal';
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function learner()
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    public function enrolment()
    {
        return $this->belongsTo(Enrolment::class, 'enrolment_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
