<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDatabaseQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:database-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test pushing to database queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Illuminate\Support\Facades\Log::info('Testing database queue dispatch started', [
            'queue_default' => config('queue.default'),
            'queue_database_connection' => config('queue.connections.database.connection'),
            'queue_database_table' => config('queue.connections.database.table'),
        ]);

        \App\Jobs\TestQueueJob::dispatch()
            ->onConnection('database')
            ->onQueue('assignment-emails');

        \Illuminate\Support\Facades\Log::info('Testing database queue dispatch finished');
    }
}
