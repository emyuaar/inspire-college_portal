<?php

namespace App\Console\Commands;

use App\Models\Crm\Enrolment;
use App\Services\LearnerActivationService;
use App\Services\PaymentActivationEligibilityService;
use Illuminate\Console\Command;

class RepairDepositActivations extends Command
{
    protected $signature = 'learners:repair-deposit-activations {--dry-run : Report only; do not activate learners}';
    protected $description = 'Activate eligible partner learners whose initial payment was confirmed before this fix.';

    public function handle(PaymentActivationEligibilityService $eligibility, LearnerActivationService $activation): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->table(['Learner', 'Enrolment', 'Order', 'Plan', 'Required', 'Confirmed', 'Status', 'Action'], collect(
            Enrolment::query()->whereNotNull('partner_learner_id')->where(fn ($q) => $q->whereNull('activation_status')->orWhere('activation_status', '!=', 'active'))->cursor()
        )->map(function (Enrolment $enrolment) use ($eligibility, $activation, $dryRun) {
            $gate = $eligibility->forEnrolment($enrolment);
            $action = $gate['eligible'] ? ($dryRun ? 'Would activate and send setup email' : 'Not eligible') : ($gate['reason'] ?: 'Payment requirement not met');
            if ($gate['eligible'] && ! $dryRun) {
                $activation->activate($enrolment);
                $action = 'Activated';
            }
            return [(string) $enrolment->partner_learner_id, $enrolment->id, $gate['order']?->id ?? '-', $gate['planType'], $this->money($gate['required']), $this->money($gate['confirmed']), $enrolment->activation_status, $action];
        })->all());
        return self::SUCCESS;
    }

    private function money(int $minor): string
    {
        return '£'.number_format($minor / 100, 2);
    }
}
