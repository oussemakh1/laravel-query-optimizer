<?php 

namespace Vendor\QueryOptimizer\Listeners;

use Vendor\QueryOptimizer\QueryAnalyzer;

class QueryListener
{
    public function handle($query)
    {
        app(QueryAnalyzer::class)->record($query->sql, $query->bindings, $query->time);
    }
}