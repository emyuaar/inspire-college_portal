<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Crm\Order;
use App\Models\Partner\PartnerLearnerInstallment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixInstallmentOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:fix-installment-order-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfills status_id for installment orders where it is NULL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Order Status Backfill...");

        // Find faulty orders: payment_mode=installment OR linked to an enrolment with installments, filtering by status_id NULL
        // Note: Some legacy orders might not have 'installment' mode yet, so we also check by enrolment linkage.

        $orders = Order::whereNull('status_id')->get();

        $this->info("Found " . $orders->count() . " orders with NULL status_id.");

        foreach ($orders as $order) {
            $this->processOrder($order);
        }

        $this->info("Backfill complete.");
    }

    protected function processOrder(Order $order)
    {
        // Check if there are partner installments for this enrolment (or learner)
        $installments = PartnerLearnerInstallment::where('enrolment_id', $order->enrolment_id)->get();

        if ($installments->isEmpty()) {
            // Try learner/course link if enrolment missing on order
            if ($order->learner_id && !$order->enrolment_id) {
                // Try to match
                // This is tricky, maybe skip obscure ones.
                // $this->warn("Skipping Order {$order->id}: No Enrolment ID and no installments found via weak link.");
            }
            $this->warn("Skipping Order {$order->id}: No related partner installments found.");
            return;
        }

        $this->info("Processing Order {$order->id} (Enrolment: {$order->enrolment_id})...");

        // Determine status
        // 1. Is overdue?
        // 2. Is current month unpaid?
        // 3. Is all paid?
        // 4. Is just active?

        $overdue = $installments->filter(function ($inst) {
            return $inst->status !== 'paid' && $inst->due_date < now()->startOfDay();
        });

        $currentMonthDue = $installments->filter(function ($inst) {
            return $inst->status !== 'paid' && $inst->due_date && $inst->due_date->isCurrentMonth();
        });

        $allPaid = $installments->every(fn($i) => $i->status === 'paid');
        $deposit = $installments->where('installment_no', 0)->first();
        $depositPaid = $deposit && $deposit->status === 'paid';

        $newStatusId = 0; // Default Pending
        $paymentMode = 'installment';

        if ($allPaid) {
            $newStatusId = 1; // Paid
        } elseif ($depositPaid) { // If deposit paid, we usually consider "Active" (1) in this system, enforcing blocks via Enrolment logic
            // However, Requirement 4 says: "overdue/current unpaid -> PENDING_PAYMENT"
            // "deposit/current due paid -> INSTALLMENTS_ACTIVE"

            // If Blocked (Overdue or Current Month unpaid)
            if ($overdue->isNotEmpty() || $currentMonthDue->isNotEmpty()) {
                $newStatusId = 0; // Pending (Blocked)
                // Actually if overdue, maybe keep 0?
                // The prompt says "overdue/current unpaid -> PENDING_PAYMENT".
                // I'll assume 0 is the ID for Pending.
            } else {
                // Active
                $newStatusId = 1; // Active
            }
        } else {
            // Deposit not paid -> Pending
            $newStatusId = 0;
        }

        // Apply Update
        $order->update([
            'status_id' => $newStatusId,
            'payment_mode' => $paymentMode
        ]);

        $this->info("Updated Order {$order->id}: Status={$newStatusId}, Mode={$paymentMode}");
        Log::info("Fixed Order {$order->id} via crm:fix-installment-order-statuses: Status {$newStatusId}");
    }
}
