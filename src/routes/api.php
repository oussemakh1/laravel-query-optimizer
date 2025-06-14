<?php 

use Illuminate\Support\Facades\Route;

use Vendor\QueryOptimizer\Http\Controllers\ApiController;
Route::prefix('api/query-optimizer')->middleware('api')->group(function(){
    Route::get('metrics', [ApiController::class,'metrics']);
    Route::post('explain', [ApiController::class, 'explain']);

});