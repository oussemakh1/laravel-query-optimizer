<?php

return [
    'log_path'         => storage_path('logs/query_optimizer.log'),
    'suggestions'      => true,
    'slow_threshold'   => 100,
    'n_plus_one_limit' => 5,
    'index_suggestions'=> true,
    'gemini_api_key' => env('GEMINI_API_KEY', ''),

];
