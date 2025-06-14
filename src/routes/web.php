<?php 
use Illuminate\Support\Facades\Route;

use Vendor\QueryOptimizer\Http\Controllers\DashboardController;

Route::middleware('web')->prefix('query-optimizer')->group(function(){
    Route::get('dashboard', [DashboardController::class,'index'])->name('queryoptimizer.dashboard');
});
