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
        'partner_id',
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

    public function partner()
    {
        return $this->belongsTo(\App\Models\User::class, 'partner_id');
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
        // -------------------------------------------------------------
        // PARTNER LOGIC: Check PartnerLearnerInstallments first
        // -------------------------------------------------------------
        $partnerInstallments = \App\Models\Partner\PartnerLearnerInstallment::where('enrolment_id', $this->id)->get();
        // Fallback: If no enrolment_id link, check via order_id if possible (but trusted link is enrolment_id)

        if ($partnerInstallments->isNotEmpty()) {

            // USE NEW LOGIC: Status depends on Access Allowed logic
            if ($this->installment_access_allowed) {
                // If access is allowed, show Active or Paid
                $total = $partnerInstallments->count();
                $paid = $partnerInstallments->where('status', 'paid')->count();

                if ($paid >= $total) {
                    return [
                        'status' => 'paid',
                        'label' => 'Paid in Full',
                        'color' => 'emerald',
                    ];
                }

                return [
                    'status' => 'installments_active',
                    'label' => 'Installments Active',
                    'color' => 'blue',
                ];
            } else {
                // Access Blocked -> Payment Pending
                return [
                    'status' => 'pending_payment',
                    'label' => 'Payment Pending',
                    'color' => 'amber',
                ];
            }
            // End New Logic override
        }

        // -------------------------------------------------------------
        // ORIGINAL LOGIC: Check Orders / OrderInstallments (Web/Self-Pay)
        // -------------------------------------------------------------
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

    /**
     * Determine if learning access is allowed based on installments.
     * Rules:
     * - Allow if: No overdue unpaid AND no current month due unpaid.
     * - Block if: Overdue unpaid OR current month due unpaid.
     */
    /**
     * Determine if learning access is allowed based on installments.
     * Rules:
     * - Partner: Block if Overdue unpaid OR Current Month due unpaid.
     * - Website: Block if "Derived Due Date" < Today and Installment N not paid.
     */
    public function getInstallmentAccessAllowedAttribute(): bool
    {
        // ------------------------------------
        // 1. PARTNER LOGIC (Explicit Due Dates)
        // ------------------------------------
        $partnerInstallments = \App\Models\Partner\PartnerLearnerInstallment::where('enrolment_id', $this->id)->get();

        if ($partnerInstallments->isNotEmpty()) {
            // Overdue (Due < Today & Unpaid)
            $overdue = $partnerInstallments->filter(function ($inst) {
                return $inst->status !== 'paid' && $inst->due_date < now()->startOfDay();
            });
            if ($overdue->isNotEmpty())
                return false;

            // Current Month Due (Due in current month & Unpaid)
            // Even if due date is later this month, if it EXISTS and is unpaid, we block?
            // "If there is OR current month unpaid installment -> hide 'Continue Learning'"
            // Usually "Current Month Unpaid" means: It is due this month, and we want them to pay it to see content.
            // Let's stick to strict: If due_date is in current month and status != paid, Block.
            $currentMonth = $partnerInstallments->filter(function ($inst) {
                return $inst->status !== 'paid' &&
                    $inst->due_date &&
                    $inst->due_date->isCurrentMonth();
            });
            if ($currentMonth->isNotEmpty())
                return false;

            return true;
        }

        // ------------------------------------
        // 2. WEBSITE LOGIC (Derived Due Dates)
        // ------------------------------------
        // Check if latest order is Installment plan
        $order = $this->latestOrder;

        // If no order, or order is Paid (1), allow? 
        // If status_id is 1 (Paid/Active), does that mean ALL are paid? 
        // In our new logic, status_id=1 just means "Not Blocked" / "Active".
        // So we must check installments if payment_mode is installment.

        if ($order && $order->payment_mode === 'installment') {
            // Plan Details
            $planMonths = $order->plan_months ?? 0;
            // If plan_months is 0/null, maybe it's full pay labeled wrong? Assume safe if 0.
            if ($planMonths < 1)
                return true;

            // Count Paid Installments (from order_installments table - Website flow)
            // Note: Website flow populates `order_installments`.
            $paidCount = $order->installments()->where('payment_status', 'paid')->count();

            // "Deposit" is usually usually covered by the main Order payment or first installment?
            // Website flow: Order created = Deposit Paid? 
            // Usually yes. If Order exists, Deposit is paid.
            // So we are checking "Monthly Installments" 1..N.

            // Derive Due Dates
            // Month 1 Due = Order Created + 1 Month
            // Month N Due = Order Created + N Months

            $now = now()->startOfDay();
            $orderDate = $order->created_at->startOfDay();

            // Check each month 1 to N
            for ($i = 1; $i <= $planMonths; $i++) {
                $dueDate = $orderDate->copy()->addMonths($i);

                // If Due Date is in the past (Overdue) OR Current Month
                // And we verify if we have enough paid installments to cover it.
                // Assuming sequential payment: PaidCount >= i means Month i is paid.

                if ($dueDate <= $now || $dueDate->isCurrentMonth()) {
                    // This month (or past month) is Due.
                    // Do we have payment for it?
                    if ($paidCount < $i) {
                        return false; // Blocked: Installment $i is due/overdue and not paid.
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get the reason for blocking access.
     */
    public function getInstallmentBlockReasonAttribute(): ?string
    {
        // ------------------------------------
        // 1. PARTNER LOGIC
        // ------------------------------------
        $partnerInstallments = \App\Models\Partner\PartnerLearnerInstallment::where('enrolment_id', $this->id)->get();

        if ($partnerInstallments->isNotEmpty()) {
            $overdue = $partnerInstallments->filter(function ($inst) {
                return $inst->status !== 'paid' && $inst->due_date < now()->startOfDay();
            });
            if ($overdue->isNotEmpty()) {
                $first = $overdue->sortBy('due_date')->first();
                $label = $first->installment_no == 0 ? "Deposit" : "Month {$first->installment_no}";
                return "Your installment for {$label} due on " . $first->due_date->format('d M Y') . " is overdue.";
            }

            $currentMonth = $partnerInstallments->filter(function ($inst) {
                return $inst->status !== 'paid' && $inst->due_date && $inst->due_date->isCurrentMonth();
            });
            if ($currentMonth->isNotEmpty()) {
                $first = $currentMonth->sortBy('due_date')->first();
                $label = $first->installment_no == 0 ? "Deposit" : "Month {$first->installment_no}";
                return "Your installment for {$label} due on " . $first->due_date->format('d M Y') . " is unpaid.";
            }
            return null;
        }

        // ------------------------------------
        // 2. WEBSITE LOGIC
        // ------------------------------------
        $order = $this->latestOrder;
        if ($order && $order->payment_mode === 'installment') {
            $planMonths = $order->plan_months ?? 0;
            if ($planMonths < 1)
                return null;

            $paidCount = $order->installments()->where('payment_status', 'paid')->count();
            $now = now()->startOfDay();
            $orderDate = $order->created_at->startOfDay();

            for ($i = 1; $i <= $planMonths; $i++) {
                $dueDate = $orderDate->copy()->addMonths($i);

                if ($dueDate <= $now || $dueDate->isCurrentMonth()) {
                    if ($paidCount < $i) {
                        // Blocked
                        $status = ($dueDate < $now) ? "is overdue" : "is unpaid";
                        return "Your installment for Month {$i} due on " . $dueDate->format('d M Y') . " {$status}.";
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get specific info (date, amount) for the Blocking/Next installment.
     * Useful for the "Pay Now" card.
     */
    public function getInstallmentDueInfoAttribute(): ?array
    {
        // Partner
        $partnerInstallments = \App\Models\Partner\PartnerLearnerInstallment::where('enrolment_id', $this->id)->get();
        if ($partnerInstallments->isNotEmpty()) {
            // Find first unpaid that is blocking or next due
            $unpaid = $partnerInstallments->where('status', '!=', 'paid')->sortBy('due_date')->first();
            if ($unpaid) {
                return [
                    'date' => $unpaid->due_date,
                    'amount' => $unpaid->installment_amount - $unpaid->paid_amount, // Remaining
                    'label' => $unpaid->installment_no == 0 ? "Deposit" : "Month {$unpaid->installment_no}",
                ];
            }
        }

        // Website
        $order = $this->latestOrder;
        if ($order && $order->payment_mode === 'installment') {
            $planMonths = $order->plan_months ?? 0;
            $paidCount = $order->installments()->where('payment_status', 'paid')->count();
            $orderDate = $order->created_at->startOfDay();

            // Next due is paidCount + 1
            $nextIndex = $paidCount + 1;
            if ($nextIndex <= $planMonths) {
                $dueDate = $orderDate->copy()->addMonths($nextIndex);
                return [
                    'date' => $dueDate,
                    'amount' => $order->plan_monthly_amount,
                    'label' => "Month {$nextIndex}",
                ];
            }
        }

        return null;
    }

    /**
     * Unified Access Status Helper
     * Returns object with:
     * - allowed: bool
     * - reason: string|null
     * - due_info: array|null
     * - has_plan: bool
     */
    public function getInstallmentAccessStatusAttribute(): object
    {
        $status = (object) [
            'allowed' => true,
            'reason' => null,
            'due_info' => null,
            'has_plan' => false,
        ];

        // ------------------------------------
        // 1. PARTNER LOGIC
        // ------------------------------------
        $partnerInstallments = \App\Models\Partner\PartnerLearnerInstallment::where('enrolment_id', $this->id)->get();
        if ($partnerInstallments->isNotEmpty()) {
            $status->has_plan = true;

            // Check Blockers
            // Overdue (Due < Today)
            $overdue = $partnerInstallments->filter(function ($inst) {
                return $inst->status !== 'paid' && $inst->due_date <= now()->startOfDay();
            });

            // Current Due (Due in Current Month) - Strict Rule: "If due in current month, must be paid to continue"
            $currentDue = $partnerInstallments->filter(function ($inst) {
                return $inst->status !== 'paid' && $inst->due_date && $inst->due_date->isCurrentMonth();
            })->reject(function ($inst) use ($overdue) {
                return $overdue->contains('id', $inst->id); // Avoid duplicates in priority logic
            });

            if ($overdue->isNotEmpty()) {
                $first = $overdue->sortBy('due_date')->first();
                $status->allowed = false;
                $label = $first->installment_no == 0 ? "Deposit" : "Month {$first->installment_no}";
                $status->reason = "Overdue: {$label}";
                $status->due_info = [
                    'date' => $first->due_date,
                    'amount' => $first->installment_amount - $first->paid_amount,
                    'label' => $label
                ];
                return $status;
            }

            if ($currentDue->isNotEmpty()) {
                $first = $currentDue->sortBy('due_date')->first();
                $status->allowed = false;
                $label = $first->installment_no == 0 ? "Deposit" : "Month {$first->installment_no}";
                $status->reason = "Due Now: {$label}";
                $status->due_info = [
                    'date' => $first->due_date,
                    'amount' => $first->installment_amount - $first->paid_amount,
                    'label' => $label
                ];
                return $status;
            }

            return $status;
        }

        // ------------------------------------
        // 2. WEBSITE LOGIC
        // ------------------------------------
        $order = $this->latestOrder;
        if ($order && $order->payment_mode === 'installment') {
            $status->has_plan = true;
            $planMonths = $order->plan_months ?? 0;
            if ($planMonths < 1)
                return $status;

            $paidCount = $order->installments()->where('payment_status', 'paid')->count();
            $now = now()->startOfDay();
            $orderDate = $order->created_at->startOfDay();

            // Check months 1..N
            for ($i = 1; $i <= $planMonths; $i++) {
                $dueDate = $orderDate->copy()->addMonths($i);

                // If Due (Past or Current Month)
                if ($dueDate <= $now || $dueDate->isCurrentMonth()) {
                    // Check payment
                    if ($paidCount < $i) {
                        // Blocked
                        $status->allowed = false;
                        $type = ($dueDate < $now) ? "Overdue" : "Due Now";
                        $status->reason = "{$type}: Month {$i}";
                        $status->due_info = [
                            'date' => $dueDate,
                            'amount' => $order->plan_monthly_amount,
                            'label' => "Month {$i}"
                        ];
                        return $status;
                    }
                }
            }
        }

        return $status;
    }
}
