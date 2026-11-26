<?php

namespace App\Providers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
   use App\Models\Student;
   use App\Models\User;
   use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
  
       Relation::morphMap([
           'user'    => User::class,
           'student' => Student::class,
       ]);


        $leadership = ['principal', 'admin', 'vice principal', 'director'];
        $staff = fn ($user, array $roles) => $user instanceof User && in_array($user->role, $roles, true);

        Gate::define('record-grades',   fn ($user) => $staff($user, ['teacher', ...$leadership]));
        Gate::define('compile-grades',  fn ($user) => $staff($user, $leadership));
        Gate::define('reopen-grades',   fn ($user) => $staff($user, $leadership));
        Gate::define('manage-teachers', fn ($user) => $staff($user, $leadership));

        Gate::define('manage-school-theme', fn ($user) => $staff($user, $leadership));
        Gate::define('manage-staff', fn ($user) => $staff($user, $leadership));

        // Only leadership registers students.
        Gate::define('register-students', fn ($user) => $staff($user, $leadership));

        // Leadership may edit any student of the school; a teacher only the students of the class they are
        // form teacher of. ASSUMPTION: users.form_teacher holds that class's name or id (e.g. "JSS1"), which
        // the codebase does not use anywhere yet. Change the one comparison below if it holds something else.
        Gate::define('update-student', function ($user, Enrollment $enrollment) use ($staff, $leadership) {
            if ($staff($user, $leadership)) {
                return true;
            }

            $class = $enrollment->myClass;

            return $staff($user, ['teacher']) && $class !== null
                && in_array((string) $user->form_teacher, [(string) $class->id, $class->name], true);
        });


   
        Schema::defaultStringLength(191);
    }
}
