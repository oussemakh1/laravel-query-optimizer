<?php

namespace Vendor\QueryOptimizer\Console\Commands;

use Illuminate\Console\Command;
use Vendor\QueryOptimizer\QueryLogger; // your stats storage handler

class ClearStats extends Command
{
    protected $signature = 'query-optimizer:clear-stats';
    protected $description = 'Clear all collected query performance statistics';

    public function handle()
    {
        QueryLogger::clear();

        $this->info('Query statistics have been cleared.');

        return 0;
    }
}
