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



   
        Schema::defaultStringLength(191);
    }
}
