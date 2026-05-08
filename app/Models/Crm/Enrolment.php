<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Website\Course;

class Enrolment extends Model
{
    use SoftDeletes;
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

    public function partnerInstallments()
    {
        return $this->hasMany(\App\Models\Partner\PartnerLearnerInstallment::class, 'enrolment_id');
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
            // Check if any installment is awaiting approval
            if ($partnerInstallments->whereIn('status', ['awaiting_approval', 'proof_submitted'])->isNotEmpty()) {
                return [
                    'status' => 'awaiting_approval',
                    'label' => 'Proof Submitted',
                    'color' => 'blue',
                ];
            }

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

        // 2. Order is Paid (status_id = 1) OR Installment Plan (status_id = 3 but active)
        if ($order->status_id == 1 || $order->payment_mode === 'installment') {

            // Check if it's an installment order
            if ($order->payment_mode === 'installment') {
                // Check if access is allowed (gating logic)
                if (!$this->installment_access_allowed) {
                     // Determine if it's due to deposit or an installment
                    $isDepositPending = ($order->plan_deposit_amount > 0 && $order->deposit_paid_amount < $order->plan_deposit_amount);

                    return [
                        'status' => 'pending_payment',
                        'label' => $isDepositPending ? 'Deposit Pending' : 'Payment Overdue',
                        'color' => $isDepositPending ? 'amber' : 'red',
                    ];
                }

                // If access allowed, detailed status
                $installments = $order->installments;
                if ($installments->count() > 0) {
                    $total = $installments->count();
                    $paid = $installments->where('payment_status', 'paid')->count();

                    if ($paid >= $total && $order->deposit_paid_amount >= $order->plan_deposit_amount) {
                         if ($order->status_id != 1) { // Failsafe visual until sync happens
                              return [
                                  'status' => 'paid',
                                  'label' => 'Paid in Full',
                                  'color' => 'emerald',
                              ];
                         }
                    }

                    if ($order->plan_deposit_amount > 0 && $paid == 0) {
                        return [
                            'status' => 'installments_active',
                            'label' => 'Deposit Paid',
                            'color' => 'blue',
                        ];
                    }

                    return [
                        'status' => 'installments_active',
                        'label' => "Installment {$paid}/{$total} Paid",
                        'color' => 'blue',
                    ];
                }
            }

            if ($order->status_id == 1) {
                return [
                    'status' => 'paid',
                    'label' => 'Paid',
                    'color' => 'emerald',
                ];
            }
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
    /**
     * Determine if learning access is allowed based on installments.
     * Uses explicit database fields (due_date, payment_status, grace_until).
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
            $currentMonth = $partnerInstallments->filter(function ($inst) {
                return $inst->status !== 'paid' &&
                    $inst->due_date &&
                    $inst->due_date->isSameMonth(now());
            });
            if ($currentMonth->isNotEmpty())
                return false;

            return true;
        }

        // ------------------------------------
        // 2. WEBSITE LOGIC (Order Installments)
        // ------------------------------------
        $order = $this->latestOrder;

        if ($order && $order->payment_mode === 'installment') {

            // DEPOSIT CHECK: Has required deposit been paid?
            if ($order->plan_deposit_amount > 0 && $order->deposit_paid_amount < $order->plan_deposit_amount) {
                // If deposit grace period exists and is valid, allow access
                $now = now();
                if ($order->deposit_grace_until && \Carbon\Carbon::parse($order->deposit_grace_until) >= $now->startOfDay()) {
                    // within grace
                } else {
                    return false; // Deposit unpaid and out of grace
                }
            }

            // Fetch installments that are NOT paid
            $unpaidInstallments = $order->installments()
                ->where('payment_status', '!=', 'paid')
                ->get();

            if ($unpaidInstallments->isEmpty()) {
                return true; // All paid
            }

            $now = now(); // Use full datetime for precision if needed, or startOfDay

            foreach ($unpaidInstallments as $inst) {
                // If grace period exists and is valid, allow access even if overdue/pending
                if ($inst->grace_until && $inst->grace_until > $now) {
                    continue;
                }

                // If Overdue
                // 'overdue' status OR (due_date passed AND no grace)
                if ($inst->payment_status === 'overdue' || ($inst->due_date && $inst->due_date < $now->startOfDay())) {
                    return false; // Block access
                }

                // If due_date is in FUTURE, we shouldn't block.
                if ($inst->due_date && $inst->due_date > $now) {
                    continue; // Future due date, don't block
                }

                // If due_date is today or past, and no grace -> Block
                return false;
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
            
            // Deposit Check First
            if ($order->plan_deposit_amount > 0 && $order->deposit_paid_amount < $order->plan_deposit_amount) {
                return "Your required deposit of £" . number_format($order->plan_deposit_amount, 2) . " is unpaid.";
            }

            $unpaidInstallments = $order->installments()
                ->where('payment_status', '!=', 'paid')
                ->get();
            
            $now = now()->startOfDay();

            foreach ($unpaidInstallments as $inst) {
                if ($inst->payment_status === 'overdue' || ($inst->due_date && $inst->due_date < $now)) {
                    if ($inst->grace_until && $inst->grace_until >= $now) {
                        return null; // In grace
                    }
                    return "Your installment for Month {$inst->installment_no} due on " . $inst->due_date->format('d M Y') . " is overdue.";
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
            
            // Deposit Pending
            if ($order->plan_deposit_amount > 0 && $order->deposit_paid_amount < $order->plan_deposit_amount) {
                return [
                    'date' => $order->created_at, // Or whenever deposit was due
                    'amount' => max(0, $order->plan_deposit_amount - $order->deposit_paid_amount),
                    'label' => "Deposit",
                ];
            }

            $unpaid = $order->installments()->where('payment_status', '!=', 'paid')->sortBy('installment_no')->first();
            if ($unpaid) {
                 return [
                    'date' => $unpaid->due_date,
                    'amount' => $unpaid->amount - ($unpaid->amount_paid ?? 0),
                    'label' => "Month {$unpaid->installment_no}",
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
                return $inst->status !== 'paid' && $inst->due_date && $inst->due_date->isSameMonth(now());
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

            // Check Deposit Check First
            if ($order->plan_deposit_amount > 0 && $order->deposit_paid_amount < $order->plan_deposit_amount) {
                $status->allowed = false;
                $status->reason = "Deposit Pending";
                $status->due_info = [
                    'date' => $order->created_at,
                    'amount' => max(0, $order->plan_deposit_amount - $order->deposit_paid_amount),
                    'label' => "Deposit"
                ];

                $now = now();
                if ($order->deposit_grace_until && \Carbon\Carbon::parse($order->deposit_grace_until) >= $now->startOfDay()) {
                    $status->allowed = true; // In grace
                    $status->grace_active = true;
                    $status->grace_until = \Carbon\Carbon::parse($order->deposit_grace_until);
                }
                
                if (!$status->allowed) return $status;
            }

            // Fetch pending installments
            $unpaidInstallments = $order->installments()
                ->where('payment_status', '!=', 'paid')
                ->get();

            $now = now()->startOfDay();

            foreach ($unpaidInstallments as $inst) {
                if ($inst->due_date && $inst->due_date < $now) {
                    if ($inst->grace_until && $inst->grace_until >= $now) {
                        // Wait, overdue but within grace -> GRACE ACTIVE
                        $status->grace_active = true;
                        $status->grace_until = $inst->grace_until;
                        $status->due_info = [
                            'date' => $inst->due_date,
                            'amount' => $inst->amount - ($inst->amount_paid ?? 0),
                            'label' => "Month {$inst->installment_no}"
                        ];
                    } else {
                        // Grace expired or missing -> BLOCKED
                        $status->allowed = false;
                        $status->grace_active = false;
                        $status->reason = "Overdue: Month {$inst->installment_no}";
                        $status->due_info = [
                            'date' => $inst->due_date,
                            'amount' => $inst->amount - ($inst->amount_paid ?? 0),
                            'label' => $inst->installment_no == 0 ? "Deposit" : "Month {$inst->installment_no}"
                        ];
                        return $status;
                    }
                }
            }
        }

        return $status;
    }
}
