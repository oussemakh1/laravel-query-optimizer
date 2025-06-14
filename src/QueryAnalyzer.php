<?php
namespace Vendor\QueryOptimizer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Filesystem\Filesystem;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection as DoctrineConnection;

/**
 * Class QueryAnalyzer
 * @package Vendor\QueryOptimizer
 */
class QueryAnalyzer
{
    protected array $config;
    protected Filesystem $fs;
    protected array $stats = [];
    protected ?DoctrineConnection $dbal = null;

    /**
     * QueryAnalyzer constructor.
     *
     * @param array $config Configuration array for the QueryAnalyzer.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->fs = new Filesystem;

        try {
            $laravelConfig = DB::getConfig();

            $doctrineConfig = [
                'dbname'   => $laravelConfig['database'],
                'user'     => $laravelConfig['username'],
                'password' => $laravelConfig['password'],
                'host'     => $laravelConfig['host'],
                'driver'   => match ($laravelConfig['driver'] ?? 'mysql') {
                    'mysql'     => 'pdo_mysql',
                    'pgsql'     => 'pdo_pgsql',
                    'sqlite'    => 'pdo_sqlite',
                    'sqlsrv'    => 'pdo_sqlsrv',
                    default     => 'pdo_mysql',
                },
                'charset' => $laravelConfig['charset'] ?? 'utf8mb4',
            ];

            $this->dbal = DriverManager::getConnection($doctrineConfig);
        } catch (\Throwable $e) {
            Log::error('[QueryOptimizer] Error initializing Doctrine DBAL: ' . $e->getMessage(), [
                'exception' => $e,
                'config' => $this->config,
            ]);
        }

        $logPath = $this->config['log_path'];
        if ($this->fs->exists($logPath)) {
            $contents = $this->fs->get($logPath);
            $lines = explode(PHP_EOL, trim($contents));
            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                if ($entry !== null) {
                    $this->stats[] = $entry;
                }
            }
        }
    }

    /**
     * Record a SQL query execution.
     *
     * @param string $sql The SQL query.
     * @param array $bindings The bindings for the query.
     * @param float $time The execution time in seconds.
     */
    public function record(string $sql, array $bindings, float $time): void
    {
        $entry = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => now()->toISOString(),
        ];

        $this->stats[] = $entry;

        $this->fs->append($this->config['log_path'], json_encode($entry) . PHP_EOL);
    }

    /**
     * Get the Doctrine DBAL connection.
     *
     * @return DoctrineConnection|null The Doctrine DBAL connection or null if not initialized.
     */
    public function getDbal(): ?DoctrineConnection
    {
        return $this->dbal;
    }

    /**
     * Get the recorded statistics.
     *
     * @return array The recorded statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Clear the recorded statistics and log file.
     *
     * @return bool True if the log file was cleared, false otherwise.
     */
    public function clear(): bool
    {
        if ($this->fs->exists($p = $this->config['log_path'])) {
            $this->fs->delete($p);
            $this->stats = [];
            return true;
        }

        return false;
    }
}
