<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Every sign-in endpoint. Each method does three things: read the request, hand it to AuthService,
 * return JSON. The rules all live in the service.
 */
class AuthController extends Controller
{
    /**
     * WHAT IT DOES: Receives the auth service. All the real work lives there; this controller only
     *    passes requests along.
     *
     * USED WHEN: Laravel creates the controller automatically for every request.
     *
     * GIVES BACK: Nothing.
     */
    public function __construct(private readonly AuthService $auth)
    {
    }

    /**
     * WHAT IT DOES: Says who is signed in, on which guard, which school is active and which
     *    schools they can switch to.
     *
     * USED WHEN: GET /me, when the app starts.
     *
     * GIVES BACK: JSON {guard, user, school_id, schools, needs_school}, guard and user null when
     *    nobody is signed in.
     */
    public function me(): JsonResponse
    {
        return response()->json($this->auth->me());
    }

    /**
     * WHAT IT DOES: Signs in. The first character of the username picks the kind of account:
     *    @ teacher/staff, % guardian, & admin, anything else student.
     *
     * USED WHEN: POST /login.
     *
     * GIVES BACK: JSON {guard, user, school_id, schools, needs_school}.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->auth->login($data['username'], $data['password'], $request->ip());

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json($result);
    }

    /**
     * WHAT IT DOES: Signs out of every guard and clears the session.
     *
     * USED WHEN: POST /logout.
     *
     * GIVES BACK: JSON {message: "out"}.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logoutAll();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'out']);
    }

    /**
     * WHAT IT DOES: Staff only. Makes one of the staff member's schools the active one. This is how
     *    a teacher or director with several schools finishes logging in.
     *
     * USED WHEN: POST /switch-school.
     *
     * GIVES BACK: JSON like /me, or 403 if they do not belong there.
     */
    public function switchSchool(Request $request): JsonResponse
    {
        $data = $request->validate(['school_id' => ['required', 'integer', 'exists:schools,id']]);

        try {
            $this->auth->switchSchool((int) $data['school_id']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($this->auth->me());
    }

    /**
     * WHAT IT DOES: Guardian only. Picks which ward to look at; the ward's school becomes the active
     *    school. This is how a guardian finishes logging in.
     *
     * USED WHEN: POST /switch-ward.
     *
     * GIVES BACK: JSON like /me, or 403 if the ward is not theirs or is not enrolled.
     */
    public function switchWard(Request $request): JsonResponse
    {
        $data = $request->validate(['ward_id' => ['required', 'integer']]);

        try {
            $this->auth->switchWard((int) $data['ward_id']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($this->auth->me());
    }
}
