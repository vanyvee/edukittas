<?php

// ---------------------------------------------------------------
// 1) config/auth.php: add the guardian guard and provider
// ---------------------------------------------------------------
// 'guards' => [
//     ...your existing guards (web, admin, customer)...
//     'guardian' => ['driver' => 'session', 'provider' => 'guardians'],
// ],
//
// 'providers' => [
//     ...your existing providers...
//     'guardians' => ['driver' => 'eloquent', 'model' => App\Models\Guardian::class],
// ],

// ---------------------------------------------------------------
// 2) The Guardian model must:
//    - extend Illuminate\Foundation\Auth\User (Authenticatable)
//    - have `username` and `password` columns, password hidden and hashed
//    - have a wards() relation to Student, e.g.
//        public function wards() { return $this->belongsToMany(Student::class, 'guardian_student'); }
// ---------------------------------------------------------------

// ---------------------------------------------------------------
// 3) Routes
// ---------------------------------------------------------------
use App\Http\Controllers\AuthController;

Route::get('register', [AuthController::class, 'showRegister']);
Route::get('me', [AuthController::class, 'me']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('switch-school', [AuthController::class, 'switchSchool']);
