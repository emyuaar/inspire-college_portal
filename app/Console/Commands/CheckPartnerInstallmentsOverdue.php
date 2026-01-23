<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Partner\PartnerLearnerInstallment;

class CheckPartnerInstallmentsOverdue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark unpaid partner installments as overdue if past due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking for overdue installments...");

        $count = 0;

        PartnerLearnerInstallment::where('status', '!=', 'paid')
            ->where('status', '!=', 'overdue')
            ->where('due_date', '<', now()->startOfDay())
            ->chunk(100, function ($installments) use (&$count) {
                foreach ($installments as $inst) {
                    $inst->update(['status' => 'overdue']);
                    $this->line("Marked overdue: ID {$inst->id} (Due: {$inst->due_date->toDateString()})");
                    $count++;
                }
            });

        $this->info("Done. Marked $count installments as overdue.");
    }
}
