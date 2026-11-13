
    <?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.any'      => \App\Http\Middleware\ResolveGuard::class,
            'ready'         => \App\Http\Middleware\EnsureReady::class,
            'role'          => \App\Http\Middleware\EnsureRole::class,
            'demo.readonly' => \App\Http\Middleware\BlockDemoWrites::class,
        ]);

        // can: must run after ResolveGuard, or it asks the wrong guard.
        $middleware->prependToPriorityList(
            before: \Illuminate\Auth\Middleware\Authorize::class,
            prepend: \App\Http\Middleware\ResolveGuard::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The old rule only returned JSON for api/*. Removing it restores Laravel's default:
        // any request that asks for JSON (axios does) gets JSON errors, including 401, 403 and 404.
    })->create();