<?php 

namespace Vendor\QueryOptimizer\Tests\Feature;
use Orchestra\Testbench\TestCase;

class ApiTest extends TestCase
{
    protected function getPackageProviders($app) { return [\Vendor\QueryOptimizer\QueryOptimizerServiceProvider::class]; }
    public function test_metrics_endpoint() {
        $this->getJson('api/query-optimizer/metrics')->assertStatus(200)->assertJsonStructure([['sql','bindings','time','timestamp']]);
    }
}