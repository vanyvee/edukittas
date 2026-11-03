<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wraps the routes that need a logged-in person. With four guards (student, web, guardian, admin),
 * Laravel only checks the default one, so $request->user() would be null for everyone else. This
 * finds the guard that is really logged in and makes it the one Laravel uses for the request.
 *
 * Use it with no arguments to allow any guard, or list the guards to allow:
 *   Route::middleware('auth.any')                 any logged-in person
 *   Route::middleware('auth.any:web,admin')       staff and admins only
 *   Route::middleware('auth.any:student')         students only
 */
class ResolveGuard
{
    /**
     * WHAT IT DOES: Receives the auth service, which knows how to find the logged-in guard.
     *
     * USED WHEN: Laravel creates the middleware automatically.
     *
     * GIVES BACK: Nothing.
     */
    public function __construct(private readonly AuthService $auth)
    {
    }

    /**
     * WHAT IT DOES: Finds the logged-in guard and makes it the default for this request, so
     *    $request->user() and auth()->user() return the right person. Stops with 401 when nobody
     *    is logged in, and with 403 when the guard is not in the list the route allows.
     *
     * USED WHEN: On every request to a route wrapped in auth.any.
     *
     * GIVES BACK: The response from the next step.
     */
    public function handle(Request $request, Closure $next, string ...$allowed): Response
    {
        $guard = $this->auth->currentGuard();

        abort_if($guard === null, 401, 'Not logged in.');
        abort_if($allowed !== [] && ! in_array($guard, $allowed, true), 403, 'Not allowed for this account.');

        Auth::shouldUse($guard);

        return $next($request);
    }
}
