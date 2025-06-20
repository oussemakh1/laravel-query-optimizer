<?php

namespace Orchestra\Testbench\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Orchestra\Sidekick\Env;
use Orchestra\Testbench\Contracts\Config as ConfigContract;
use Orchestra\Testbench\Foundation\Actions\DeleteVendorSymlink;
use Orchestra\Testbench\Workbench\Actions\RemoveAssetSymlinkFolders;
use Symfony\Component\Console\Attribute\AsCommand;

use function Orchestra\Sidekick\join_paths;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'package:purge-skeleton', description: 'Purge skeleton folder to original state')]
class PurgeSkeletonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:purge-skeleton';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @param  \Orchestra\Testbench\Contracts\Config  $config
     * @return int
     */
    public function handle(Filesystem $filesystem, ConfigContract $config)
    {
        $this->call('config:clear');
        $this->call('event:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        (new RemoveAssetSymlinkFolders($filesystem, $config))->handle();

        ['files' => $files, 'directories' => $directories] = $config->getPurgeAttributes();

        $environmentFile = Env::get('TESTBENCH_ENVIRONMENT_FILENAME', '.env');

        (new Actions\DeleteFiles(
            filesystem: $filesystem,
        ))->handle(
            Collection::make([
                $environmentFile,
                "{$environmentFile}.backup",
                join_paths('bootstrap', 'cache', 'testbench.yaml'),
                join_paths('bootstrap', 'cache', 'testbench.yaml.backup'),
            ])->map(fn ($file) => $this->laravel->basePath($file))
        );

        (new Actions\DeleteFiles(
            filesystem: $filesystem,
        ))->handle(
            LazyCollection::make(function () use ($filesystem) {
                yield $this->laravel->basePath(join_paths('database', 'database.sqlite'));
                yield $filesystem->glob($this->laravel->basePath(join_paths('routes', 'testbench-*.php')));
                yield $filesystem->glob($this->laravel->basePath(join_paths('storage', 'app', 'public', '*')));
                yield $filesystem->glob($this->laravel->basePath(join_paths('storage', 'app', '*')));
                yield $filesystem->glob($this->laravel->basePath(join_paths('storage', 'framework', 'sessions', '*')));
            })->flatten()
        );

        (new Actions\DeleteFiles(
            filesystem: $filesystem,
            components: $this->components,
        ))->handle(
            LazyCollection::make($files)
                ->map(fn ($file) => $this->laravel->basePath($file))
                ->map(static function ($file) use ($filesystem) {
                    return str_contains($file, '*')
                        ? [...$filesystem->glob($file)]
                        : $file;
                })->flatten()
                ->reject(fn ($file) => str_contains($file, '*'))
        );

        (new Actions\DeleteDirectories(
            filesystem: $filesystem,
            components: $this->components,
        ))->handle(
            Collection::make($directories)
                ->map(fn ($directory) => $this->laravel->basePath($directory))
                ->map(static function ($directory) use ($filesystem) {
                    return str_contains($directory, '*')
                        ? [...$filesystem->glob($directory)]
                        : $directory;
                })->flatten()
                ->reject(static function ($directory) {
                    return str_contains($directory, '*');
                })
        );

        TerminatingConsole::before(function () {
            (new DeleteVendorSymlink)->handle($this->laravel);
        });

        return Command::SUCCESS;
    }
}
