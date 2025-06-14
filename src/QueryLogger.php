<?php

namespace Vendor\QueryOptimizer;

class QueryLogger
{
    protected string $logPath;

    public function __construct()
    {
        $this->logPath = config('queryoptimizer.log_path', storage_path('logs/query_optimizer.log'));
    }

    public function getStats(): array
    {
        if (!file_exists($this->logPath)) {
            return [];
        }

        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $queryStats = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);

            if (!is_array($entry) || !isset($entry['sql'], $entry['time'])) {
                continue;
            }

            $sql = $entry['sql'];
            $time = (float) $entry['time'];

            if (!isset($queryStats[$sql])) {
                $queryStats[$sql] = [
                    'count' => 0,
                    'total_time' => 0.0,
                ];
            }

            $queryStats[$sql]['count']++;
            $queryStats[$sql]['total_time'] += $time;
        }

        return $queryStats;
    }

    public function clear(): void
    {
        file_put_contents($this->logPath, '');
    }
}
