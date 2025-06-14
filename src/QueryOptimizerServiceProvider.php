<?php

namespace Vendor\QueryOptimizer;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\ServiceProvider;
use Vendor\QueryOptimizer\QueryAnalyzer;

use Vendor\QueryOptimizer\Listeners\QueryListener;

class QueryOptimizerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/queryoptimizer.php', 'queryoptimizer');
       
        $this->app->singleton(QueryAnalyzer::class, function ($app) {
            $config = config('queryoptimizer');
            return new QueryAnalyzer($config);
        });
    }
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/queryoptimizer.php' => config_path('queryoptimizer.php'),
        ], 'config');
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/queryoptimizer'),
        ], 'queryoptimizer-views');
        DB::listen(function ($query) {
            (new QueryListener())->handle($query);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Vendor\QueryOptimizer\Console\Commands\ShowStats::class,
                \Vendor\QueryOptimizer\Console\Commands\ClearStats::class,
            ]);
        }

        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
            $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        }

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'queryoptimizer');
        $this->publishes([__DIR__ . '/resources/js' => resource_path('js/queryoptimizer')], 'assets');
    }
}
