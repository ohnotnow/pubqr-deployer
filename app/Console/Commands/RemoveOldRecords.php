<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Deployment;

class RemoveOldRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pubqr:remove-old-records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove old deployment records from the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Deployment::where('updated_at', '<', now()->subDays(config('pubqr.record_retention_days', 14)))
            ->delete();
    }
}
