<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestGraphMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'graph-mail:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending an email via Microsoft Graph API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Starting manual test for Graph email to: {$email}");

        try {
            $graph = new \App\Services\MicrosoftGraphService();
            $subject = 'Test Microsoft Graph API SendMail';
            $body = '<h1>Hello from DirectSkills</h1><p>This is a manual test email via Microsoft Graph API.</p>';
            
            $graph->sendMail($subject, $body, $email);
            
            $this->info("Test email successfully sent to {$email} via Microsoft Graph API.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to send test email: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Manual TestGraphMail failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
