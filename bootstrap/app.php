<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
         //api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //

    $middleware->alias([
        'auth.admin'    => \App\Http\Middleware\AdminAuthWare::class,
        'auth.student'  => \App\Http\Middleware\StudentAuthWare::class,
        'auth.user'     => \App\Http\Middleware\UserAuthWare::class,
        'auth.guardian' => \App\Http\Middleware\GuardAuthWare::class,
        'auth.any' => \App\Http\Middleware\ResolveGuard::class,
        'role'     => \App\Http\Middleware\EnsureRole::class,
       
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
