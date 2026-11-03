<?php

namespace App\Http\Middleware;

use App\Support\Helper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wraps the school routes. Almost everything in the school world reads session('school_id'), and
 * scoped models stop the request with a 500 when it is missing. This turns that into a clean 409
 * the app can answer by showing the school or ward picker.
 *
 * Usage:
 *   Route::middleware(['auth.any:web,student,guardian', 'ready'])      a school must be active
 *   Route::middleware('ready:ward')                                    a ward must be chosen too (guardian routes)
 */
class EnsureReady
{
    /**
     * WHAT IT DOES: Lets the request through only when a school is active, and, when the route says
     *    ready:ward, when a ward is chosen as well. Otherwise it answers 409 with a flag the app
     *    can act on.
     *
     * USED WHEN: On every request to a route wrapped in ready.
     *
     * GIVES BACK: The response from the next step, or a 409 JSON {message, needs_school or needs_ward}.
     */
    public function handle(Request $request, Closure $next, string ...$need): Response
    {
        if (Helper::currentSchoolId() === null) {
            return response()->json(['message' => 'Select a school first.', 'needs_school' => true], 409);
        }

        if (in_array('ward', $need, true) && Helper::currentWardId() === null) {
            return response()->json(['message' => 'Select a ward first.', 'needs_ward' => true], 409);
        }

        return $next($request);
    }
}
