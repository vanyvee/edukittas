<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Narrows a route to certain staff roles. Directors, principals, school admins and teachers are all
 * User rows told apart by the `role` column, so this reads that column on the staff (web) guard.
 *
 * It is meant to sit inside auth.any, which checks who is logged in; this one checks what they are:
 *   Route::middleware('auth.any:web')->group(function () {
 *       Route::middleware('role:director')->group(...);                  director only
 *       Route::middleware('role:principal,schooladmin')->group(...);     either of the two
 *   });
 */
class EnsureRole
{
    /**
     * WHAT IT DOES: Lets the request through only when the logged-in staff member's role is one of
     *    the listed roles. Anyone else, including a student, guardian or admin, gets a 403.
     *
     * USED WHEN: On every request to a route wrapped in role:...
     *
     * GIVES BACK: The response from the next step.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Asks the web guard directly, so it works whatever the guard middleware did before it.
        $user = $request->user('web');

        abort_unless($user && in_array($user->role, $roles, true), 403, 'Not allowed for your role.');

        return $next($request);
    }
}
