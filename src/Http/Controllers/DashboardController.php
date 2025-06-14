<?php 

namespace Vendor\QueryOptimizer\Http\Controllers;
use Illuminate\Routing\Controller;
use Vendor\QueryOptimizer\QueryAnalyzer;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = app(QueryAnalyzer::class)->getStats();
        return view('vendor.queryoptimizer.dashboard', compact('stats'));
    }
}