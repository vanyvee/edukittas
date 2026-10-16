<?php

// Put these inside your existing authenticated route group (routes/api.php or web.php).

use App\Http\Controllers\CbtController;
use App\Http\Controllers\CbtAttemptController;

// Authoring
Route::apiResource('cbts', CbtController::class);

// Taking (already built)
Route::post('cbts/{cbt}/attempts', [CbtAttemptController::class, 'start']);
Route::put('attempts/{attempt}/answers', [CbtAttemptController::class, 'save']);
Route::post('attempts/{attempt}/submit', [CbtAttemptController::class, 'submit']);
Route::get('attempts/{attempt}/result', [CbtAttemptController::class, 'result']);
