<?php

namespace App\Providers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
   use App\Models\Student;
   use App\Models\User;
   use Illuminate\Database\Eloquent\Relations\Relation;

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
   
        Schema::defaultStringLength(191);
    }
}
