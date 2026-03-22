<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ThreadController;

// Auth Routes
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Protected Routes
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/refresh', [UserController::class, 'refresh']);
    Route::get('/me', [UserController::class, 'me']);
    
    // Threads
    Route::apiResource('threads', ThreadController::class);

    // Wall & Feed Networking
    Route::get('/wall', [App\Http\Controllers\Api\WallController::class, 'index']);
    Route::post('/follow/{user}', [App\Http\Controllers\Api\WallController::class, 'follow']);
});

// Prometheus metrics endpoint (unprotected for scraping)
Route::get('/metrics', function () {
    $threads = \App\Models\Thread::count();
    $users = \App\Models\User::count();
    $memory = memory_get_usage(true);
    $errors = \Illuminate\Support\Facades\Redis::get('metrics:app_errors_total') ?? 0;
    
    $out = "# HELP threads_total The total number of threads.\n";
    $out .= "# TYPE threads_total gauge\n";
    $out .= "threads_total {$threads}\n\n";
    
    $out .= "# HELP users_total The total number of users.\n";
    $out .= "# TYPE users_total gauge\n";
    $out .= "users_total {$users}\n\n";

    $out .= "# HELP php_memory_usage_bytes The memory usage of the PHP process in bytes.\n";
    $out .= "# TYPE php_memory_usage_bytes gauge\n";
    $out .= "php_memory_usage_bytes {$memory}\n\n";

    $out .= "# HELP app_errors_total The total number of backend exceptions.\n";
    $out .= "# TYPE app_errors_total counter\n";
    $out .= "app_errors_total {$errors}\n";

    return response($out)->header('Content-Type', 'text/plain');
});
