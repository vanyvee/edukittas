<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\EnquiryService;
use App\Services\PublicSchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The public pages' data. No login, nothing taken from the session's school.
 */
class PublicController extends Controller
{
    /**
     * WHAT IT DOES: Receives the services. All the real work lives there; this controller only passes
     *    requests along.
     *
     * USED WHEN: Laravel creates the controller automatically for every request.
     *
     * GIVES BACK: Nothing.
     */
    public function __construct(
        private readonly PublicSchoolService $schools,
        private readonly AffiliateService $affiliates,
        private readonly EnquiryService $enquiries,
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
     * WHAT IT DOES: Returns a school's public profile from its short link code. A code that cannot be
     *    a real one is refused straight away.
     *
     * USED WHEN: GET /public/schools/by-code/{code}, when a school's own page opens by client-side navigation.
     *
     * GIVES BACK: JSON profile.
     */
    public function schoolByCode(string $code): JsonResponse
    {
        abort_unless(preg_match('/^(?=.*[a-zA-Z])[a-zA-Z_]{1,19}$/D', $code), 404);

        $profile = $this->schools->byCode($code);

        abort_if($profile === null, 404);

        return response()->json($profile);
    }

    /**
     * WHAT IT DOES: Takes an admission enquiry for a school. The answer is the same whether or not it
     *    was a repeat.
     *
     * USED WHEN: POST /public/schools/{id}/enquiries, from the school page's Admissions slide.
     *
     * GIVES BACK: JSON {ok: true} with status 202.
     */
    public function enquire(Request $request, int $id): JsonResponse
    {
        abort_if($this->schools->profile($id) === null, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $this->enquiries->submit($id, $data, $request->ip());

        return response()->json(['ok' => true], 202);
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

    /**
     * WHAT IT DOES: Returns the demo school's name and the demo accounts (username and password) for
     *    the guest page's demo screen. A 404 when no demo school is configured.
     *
     * USED WHEN: GET /public/demo.
     *
     * GIVES BACK: JSON {school, accounts}.
     */
    public function demo(): JsonResponse
    {
        $demo = $this->schools->demo();

        abort_if($demo === null, 404);

        return response()->json($demo);
    }
}
