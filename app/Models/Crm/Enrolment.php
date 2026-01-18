<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use App\Models\Website\Course;

class Enrolment extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'enrolments';

    protected $fillable = [
        'course_id',
        'learner_id',
        'status_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function learner()
    {
        return $this->belongsTo(\App\Models\User::class, 'learner_id');
    }

    public function status()
    {
        return $this->belongsTo(EnrolmentStatus::class, 'status_id');
    }

    public function latestOrder()
    {
        return $this->hasOne(Order::class, 'enrolment_id')->latestOfMany();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'enrolment_id');
    }

    /**
     * Get dynamic payment status details for UI
     * Returns array: ['status' => string, 'label' => string, 'color' => string]
     */
    public function getPaymentStatusDetailsAttribute(): array
    {
        $order = $this->latestOrder;

        // 1. No Order -> Pending Payment
        if (!$order) {
            return [
                'status' => 'pending_payment',
                'label' => 'Payment Pending',
                'color' => 'amber', // amber-100/800
            ];
        }

        // 2. Order is Paid (status_id = 1)
        // We assume status_id 1 is Paid based on PaymentProcessingService
        if ($order->status_id == 1) {
            
            // Check for Installments
            $installments = $order->installments; // Ensure this is loaded or lazy-loaded
            
            if ($installments->count() > 0) {
                $total = $installments->count();
                $paid = $installments->where('payment_status', 'paid')->count();

                if ($paid >= $total) {
                    return [
                        'status' => 'paid',
                        'label' => 'Paid in Full',
                        'color' => 'emerald',
                    ];
                } else {
                    return [
                        'status' => 'installments_active',
                        'label' => "Installment {$paid}/{$total} Paid",
                        'color' => 'blue',
                    ];
                }
            }

            return [
                'status' => 'paid',
                'label' => 'Paid',
                'color' => 'emerald',
            ];
        }

        // 3. Order Exists but not Paid (Pending)
        return [
            'status' => 'pending_payment',
            'label' => 'Payment Pending',
            'color' => 'amber',
        ];
    }
}
