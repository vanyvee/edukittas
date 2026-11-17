<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\PublicSchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The guest page's data. No login, nothing taken from the session's school.
 */
class PublicController extends Controller
{
    /**
     * WHAT IT DOES: Receives the two services. All the real work lives there; this controller only
     *    passes requests along.
     *
     * USED WHEN: Laravel creates the controller automatically for every request.
     *
     * GIVES BACK: Nothing.
     */
    public function __construct(
        private readonly PublicSchoolService $schools,
        private readonly AffiliateService $affiliates,
    ) {
    }

    /**
     * WHAT IT DOES: Lists the featured schools.
     *
     * USED WHEN: GET /public/schools/featured.
     *
     * GIVES BACK: JSON list.
     */
    public function featured(): JsonResponse
    {
        return response()->json($this->schools->featured());
    }

    /**
     * WHAT IT DOES: Returns one school's public profile, or 404 when it does not exist or is not active.
     *
     * USED WHEN: GET /public/schools/{id}.
     *
     * GIVES BACK: JSON profile.
     */
    public function school(int $id): JsonResponse
    {
        $profile = $this->schools->profile($id);

        abort_if($profile === null, 404);

        return response()->json($profile);
    }

    /**
     * WHAT IT DOES: Takes an affiliate application. The answer is the same whether or not it was a
     *    repeat, so the form cannot be used to find out who has already applied.
     *
     * USED WHEN: POST /public/affiliates, from the Affiliate slide.
     *
     * GIVES BACK: JSON {ok: true} with status 202.
     */
    public function applyAffiliate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->affiliates->apply($data, $request->ip());

        return response()->json(['ok' => true], 202);
    }


    public function demo(): JsonResponse
{
    $demo = $this->schools->demo();
    abort_if($demo === null, 404);

    return response()->json($demo);
}
}
