<?php

namespace App\Http\Middleware;

use App\Support\Helper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the demo school read-only. Anyone logged in to a school flagged is_demo can open every page
 * (GET), but every request that would change data (POST, PUT, PATCH, DELETE) is refused here, before
 * any controller runs. Controllers and services need no demo checks at all.
 *
 * Wrap it around the school routes, after `ready` (it needs the active school):
 *   Route::middleware(['auth.any:web,student,guardian', 'ready', 'demo.readonly'])
 *
 * A POST that only reads (a preview or a check) can be let through on its own route:
 *   Route::post(...)->withoutMiddleware('demo.readonly');
 */
class BlockDemoWrites
{
    /**
     * WHAT IT DOES: Refuses any request that is not a plain read when the active school is the demo
     *    school. Everything else passes untouched.
     *
     * USED WHEN: On every request inside the school routes.
     *
     * GIVES BACK: The response from the next step, or a 403 JSON {message, demo: true}.
     */


public function handle(Request $request, Closure $next): Response
{
    $demoId = config('demo.school_id');

    if ($demoId !== null && ! $request->isMethodSafe() && (int) Helper::currentSchoolId() === (int) $demoId) {
        return response()->json([
            'message' => 'This is a demo school. Changes are disabled.',
            'demo' => true,
        ], 403);
    }

    return $next($request);
}











}








