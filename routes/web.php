<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This is an API backend. Web routes are disabled.
| Use /api/* endpoints instead.
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'FaddedSMS API Backend',
        'version' => '1.0.0',
        'status' => 'running',
        'endpoints' => [
            'api' => '/api',
            'test' => '/api/test',
            'services' => '/api/services'
        ]
    ]);
});
