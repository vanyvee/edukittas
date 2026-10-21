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





Route::middleware('auth:sanctum')->group(function () {
    Route::get('cbts/available', [CbtController::class, 'available']);
    Route::get('cbts', [CbtController::class, 'index']);
    Route::post('cbts', [CbtController::class, 'store']);
    Route::get('cbts/{cbt}', [CbtController::class, 'show']);
    Route::put('cbts/{cbt}', [CbtController::class, 'update']);
    Route::delete('cbts/{cbt}', [CbtController::class, 'destroy']);
    Route::post('cbts/{cbt}/save-to-bank', [CbtController::class, 'saveToBank']);

    Route::post('cbts/{cbt}/attempts', [CbtController::class, 'start']);
    Route::put('attempts/{attempt}/answers', [CbtController::class, 'saveAnswers']);
    Route::post('attempts/{attempt}/submit', [CbtController::class, 'submit']);
    Route::get('attempts/{attempt}/result', [CbtController::class, 'result']);

    Route::post('cbt-bank/request', [CbtController::class, 'requestQuestions']);
    Route::put('cbt-bank/questions/{question}', [CbtController::class, 'updateQuestion']);
    Route::delete('cbt-bank/questions/{question}', [CbtController::class, 'removeQuestion']);
    Route::delete('cbt-bank/groups/{group}', [CbtController::class, 'removeGroup']);

    Route::get('cbt-admin/vetting', [CbtController::class, 'vettingList']);
    Route::post('cbt-admin/vet', [CbtController::class, 'vet']);
    Route::post('cbt-admin/unvet', [CbtController::class, 'unvet']);
    Route::post('cbt-admin/promote', [CbtController::class, 'promote']);
});