<?php

namespace App\Http\Controllers;

use App\Models\Cbt;
use App\Models\CbtAttempt;
use App\Models\Student;
use App\Services\Cbt\CbtService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CbtAttemptController extends Controller
{
    public function __construct(private readonly CbtService $cbts)
    {
    }

    /** POST /cbts/{cbt}/attempts: start, or resume, the attempt. */
    public function start(Request $request, Cbt $cbt): JsonResponse
    {
        $attempt = $this->cbts->startAttempt($cbt, $this->taker($request));

        return response()->json($this->cbts->attemptPayload($attempt));
    }

    /** PUT /attempts/{attempt}/answers: autosave. */
    public function save(Request $request, CbtAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        $data = $request->validate(['answers' => ['present', 'array', 'max:500']]);
        $this->cbts->saveAnswers($attempt, $data['answers']);

        return response()->json(['ok' => true]);
    }

    /** POST /attempts/{attempt}/submit: final answers, grade, result. */
    public function submit(Request $request, CbtAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        $data = $request->validate([
            'answers' => ['present', 'array', 'max:500'],
            'answers.*.id' => ['required', 'integer'],
            'answers.*.answer' => ['nullable'],
        ]);

        return response()->json($this->cbts->submit($attempt, $data['answers']));
    }

    /** GET /attempts/{attempt}/result */
    public function result(Request $request, CbtAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        return response()->json($this->cbts->resultFor($attempt));
    }

    /** Students act through their current enrollment; teachers and staff act as themselves. */
    private function taker(Request $request): Model
    {
        $user = $request->user();

        $taker = $user instanceof Student ? $user->currentEnrollment() : $user;

        return $taker ?? abort(403, 'No active enrollment.');
    }

    /** Only the person who owns an attempt may touch it. */
    private function authorizeAttempt(Request $request, CbtAttempt $attempt): void
    {
        abort_unless($attempt->isOwnedBy($this->taker($request)), 403);
    }
}
