<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CbtController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\TeacherController;
use App\Models\Cbt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;
/*
|--------------------------------------------------------------------------
| 1. GUEST: no login needed
|--------------------------------------------------------------------------
*/

Route::get('me', [AuthController::class, 'me']);   // answers "nobody" itself, so it stays outside auth.any
Route::get('register', [AuthController::class, 'showRegister']);
Route::post('login', [AuthController::class, 'login']);


// MAIN EDUKITTAS DOMAIN
Route::domain(config('domains.main'))->group(function () {
    Route::get('/', [LandingController::class, 'home']);
});

// CODED ENTRY DOMAIN
Route::domain(config('domains.coded'))->group(function () {
    Route::get('/{code}', [LandingController::class, 'codedEntry'])
        ->where('code', '[A-Za-z0-9_-]+');
});




/*
|--------------------------------------------------------------------------
| 2. ANY LOGGED-IN PERSON, before a school or ward is chosen
|    (no `ready` here on purpose: these must work while the picker is showing)
|--------------------------------------------------------------------------
*/

Route::middleware('auth.any')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('switch-school', [AuthController::class, 'switchSchool']);
    // Route::post('switch-ward', [AuthController::class, 'switchWard']);   // guardians; add when the method exists
});

/*
|--------------------------------------------------------------------------
| 3. PLATFORM ADMIN: its own world, never mixed with the school world
|--------------------------------------------------------------------------
*/
Route::middleware('auth.any:admin')->prefix('admin')->group(function () {
    // platform admin routes
});

/*
|--------------------------------------------------------------------------
| 4. SCHOOL WORLD: staff, students and guardians, with a school active
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.any:web,student,guardian', 'ready'])->group(function () {

    // Shared by all three: home, school info, anything every logged-in person may see.
    // Route::get('home', ...);

    // ---- 4a. Strictly student ---------------------------------------
    Route::middleware('auth.any:student')->group(function () {
        // student-only routes
    });

    // ---- 4b. Strictly guardian (a ward must be chosen) --------------
    Route::middleware(['auth.any:guardian', 'ready:ward'])->group(function () {
        // guardian-only routes
    });

    // ---- 4c. CBT: students and staff (guardians do not take or set up CBTs) ----
    Route::middleware('auth.any:student,web')->group(function () {
        Route::get('cbts/available', [CbtController::class, 'available']);   // keep above cbts/{cbt}

        Route::get('cbts', [CbtController::class, 'index'])->middleware('can:viewAny,' . Cbt::class);
        Route::post('cbts', [CbtController::class, 'store'])->middleware('can:create,' . Cbt::class);
        Route::get('cbts/{cbt}', [CbtController::class, 'show'])->middleware('can:view,cbt');
        Route::put('cbts/{cbt}', [CbtController::class, 'update'])->middleware('can:update,cbt');
        Route::delete('cbts/{cbt}', [CbtController::class, 'destroy'])->middleware('can:delete,cbt');
        Route::post('cbts/{cbt}/save-to-bank', [CbtController::class, 'saveToBank'])->middleware('can:update,cbt');

        Route::post('cbts/{cbt}/attempts', [CbtController::class, 'start'])->middleware('can:take,cbt');
        Route::put('attempts/{attempt}/answers', [CbtController::class, 'saveAnswers'])->middleware('can:own,attempt');
        Route::post('attempts/{attempt}/submit', [CbtController::class, 'submit'])->middleware('can:own,attempt');
        Route::get('attempts/{attempt}/result', [CbtController::class, 'result'])->middleware('can:own,attempt');
    });

    // ---- 4d. STAFF ---------------------------------------------------
    Route::middleware('auth.any:web')->group(function () {

        // any staff member
        Route::get('teachers', [TeacherController::class, 'index']);
        Route::get('teachers/{teacher}', [TeacherController::class, 'show'])->whereNumber('teacher');
        Route::get('teachers/{teacher}/assignments', [TeacherController::class, 'assignments'])->whereNumber('teacher');

        // question bank (the service decides who may edit or remove what)
        Route::post('cbt-bank/request', [CbtController::class, 'requestQuestions']);
        Route::put('cbt-bank/questions/{question}', [CbtController::class, 'updateQuestion']);
        Route::delete('cbt-bank/questions/{question}', [CbtController::class, 'removeQuestion']);
        Route::delete('cbt-bank/groups/{group}', [CbtController::class, 'removeGroup']);

        // recording scores: teachers and leadership (a janitor must not reach these)
        Route::middleware('can:record-grades')->group(function () {
            Route::get('grades/sheet', [GradeController::class, 'sheet']);
            Route::post('grades/scores', [GradeController::class, 'record']);
            Route::get('grades/students/{student}', [GradeController::class, 'forStudent'])->whereNumber('student');
            Route::get('grades/pushable', [GradeController::class, 'pushable']);
            Route::post('grades/push/preview', [GradeController::class, 'previewPush']);
            Route::post('grades/push', [GradeController::class, 'push']);
        });

        // leadership only
        Route::post('grades/compile', [GradeController::class, 'compile'])->middleware('can:compile-grades');
        Route::post('grades/reopen', [GradeController::class, 'reopen'])->middleware('can:reopen-grades');

        // managing teachers
        Route::middleware('can:manage-teachers')->group(function () {
            Route::post('teachers', [TeacherController::class, 'store']);
            Route::post('teachers/confirm', [TeacherController::class, 'confirm']);
            Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->whereNumber('teacher');
            Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->whereNumber('teacher');
            Route::post('teachers/{teacher}/assignments', [TeacherController::class, 'assign'])->whereNumber('teacher');
            Route::post('teachers/{teacher}/assignments/check', [TeacherController::class, 'check'])->whereNumber('teacher');
            Route::post('teachers/{teacher}/assignments/deassign', [TeacherController::class, 'deassign'])->whereNumber('teacher');
        });

        // CBT vetting (admin): confirm which world this belongs to before relying on it
        Route::middleware('role:admin')->group(function () {
            Route::get('cbt-admin/vetting', [CbtController::class, 'vettingList']);
            Route::post('cbt-admin/vet', [CbtController::class, 'vet']);
            Route::post('cbt-admin/unvet', [CbtController::class, 'unvet']);
            Route::post('cbt-admin/promote', [CbtController::class, 'promote']);
        });
    });
});
Route::get('/{any}', fn () => view('app'))->where('any', '.*');