<?php

declare(strict_types=1);

namespace ZupiterDoplac\Domain\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use ZupiterDoplac\Domain\Supports\DomainSupport;
use Illuminate\Console\Command;

class DomainMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:migrate {--fresh : Drop all tables and re-run all migrations} {  --force : Force migrate }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations from all domain';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $fresh = $this->option('fresh');

        $force = $this->option('force');

        $forceOption = $force ? ['--force' => true] : [] ;

        if ($fresh) {
            $this->call('migrate:fresh', $forceOption) ;
            return;
        } else {
            $this->call('migrate', $forceOption);
        }

        $this->info('Migrations completed for App----');

        $support = DomainSupport::init(true);
        $domains =  $support->getDomains();

        $desiredOrder = [
            'EmailMarketing', 'ColdOutreach', 'TeamInbox', 'Booking', 
            'Social', 'Cms', 'ClientPortal','BillingInvoice', 'Support', 'MediaLibrary', 'Automation', 'ProjectManagement'
        ];

        $sortedArray = array_merge(array_flip($desiredOrder), $domains);
        $sortedArray = array_intersect_key($sortedArray, $domains);

        $domains =  $sortedArray;

        foreach ($domains as $domain) {

            $this->info('Start migration from '.$domain['title']);
            
            $this->call('migrate',  ['--path' => $domain['real_path'].'/../database/migrations', ...$forceOption]);

            $this->info('Completed migration from '.$domain['title']);

            config(['database.connections.mysql.prefix' => '']);
        }

        $this->alert('All domain\'s migration completed.');
    }
}
