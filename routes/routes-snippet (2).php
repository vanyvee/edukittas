<?php

// Inside your authenticated (and admin-only) route group.

use App\Http\Controllers\TeacherController;

Route::get('teachers', [TeacherController::class, 'index']);
Route::post('teachers', [TeacherController::class, 'store']);
Route::post('teachers/confirm', [TeacherController::class, 'confirm']);   // keep above {teacher}

Route::prefix('teachers/{teacher}')->whereNumber('teacher')->group(function () {
    Route::get('/', [TeacherController::class, 'show']);
    Route::put('/', [TeacherController::class, 'update']);
    Route::delete('/', [TeacherController::class, 'destroy']);

    Route::get('assignments', [TeacherController::class, 'assignments']);
    Route::post('assignments', [TeacherController::class, 'assign']);
    Route::post('assignments/check', [TeacherController::class, 'check']);
    Route::post('assignments/deassign', [TeacherController::class, 'deassign']);
});
