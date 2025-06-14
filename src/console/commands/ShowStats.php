<?php

namespace Vendor\QueryOptimizer\Console\Commands;

use Illuminate\Console\Command;
use Vendor\QueryOptimizer\QueryLogger;

class ShowStats extends Command
{
    protected $signature = 'query-optimizer:stats';
    protected $description = 'Show optimized query statistics from the log file';

    public function handle()
    {
        $logger = new QueryLogger();
        $stats = $logger->getStats();

        if (empty($stats)) {
            $this->info('No query data available.');
            return;
        }

        $table = [];

        foreach ($stats as $sql => $data) {
            $table[] = [
                'Query' => strlen($sql) > 60 ? substr($sql, 0, 57) . '...' : $sql,
                'Time (ms)' => round($data['total_time'], 2),
                'Count' => $data['count'],
            ];
        }

        usort($table, fn ($a, $b) => $b['Time (ms)'] <=> $a['Time (ms)']);

        $this->table(
            ['SQL', 'Total Time (ms)', 'Count'],
            $table
        );
    }
}
