<?php

use App\Http\Controllers\GradeController;

// Staff (teachers included)
Route::post('grades/scores', [GradeController::class, 'record']);
Route::get('grades/students/{student}', [GradeController::class, 'forStudent'])->whereNumber('student');

// Admin only: put these behind your admin middleware
Route::post('grades/compile', [GradeController::class, 'compile']);
Route::post('grades/reopen', [GradeController::class, 'reopen']);





Route::get('grades/pushable', [GradeController::class, 'pushable']);
Route::post('grades/push/preview', [GradeController::class, 'previewPush']);
Route::post('grades/push', [GradeController::class, 'push']);