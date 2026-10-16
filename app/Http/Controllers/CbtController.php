<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCbtActor;
use App\Http\Requests\CbtRequest;
use App\Models\Cbt;
use App\Services\Cbt\CbtAuthoringService;
use App\Services\Cbt\QuestionValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CbtController extends Controller
{
    use ResolvesCbtActor;

    public function __construct(
        private readonly CbtAuthoringService $authoring,
        private readonly QuestionValidator $validator,
    ) {
    }

    /** GET /cbts: the CBTs this person set up. */
    public function index(Request $request): JsonResponse
    {
        $actor = $this->cbtActor($request);

        $cbts = Cbt::query()
            ->where('host_type', $actor->getMorphClass())
            ->where('host_id', $actor->getKey())
            ->withCount(['tempQuestions as questions_count', 'attempts'])
            ->latest('id')
            ->get(['id', 'title', 'type', 'duration_minutes', 'opens_at', 'closes_at', 'expires_at', 'created_at']);

        return response()->json($cbts);
    }

    /** POST /cbts: create a CBT together with its questions. */
    public function store(CbtRequest $request): JsonResponse
    {
        $actor = $this->cbtActor($request);

        $clean = $this->validator->validate(['blocks' => $request->input('blocks', [])]);

        $cbt = $this->authoring->create($actor, $request->meta(), $clean['blocks']);

        return response()->json($this->authoring->payload($cbt), 201);
    }

    /** GET /cbts/{cbt}: everything the editing panel needs (answer key included). */
    public function show(Request $request, Cbt $cbt): JsonResponse
    {
        $this->authorizeHost($request, $cbt);

        return response()->json($this->authoring->payload($cbt));
    }

    /** PUT/PATCH /cbts/{cbt}: change settings and/or replace the questions. */
    public function update(CbtRequest $request, Cbt $cbt): JsonResponse
    {
        $this->authorizeHost($request, $cbt);

        $blocks = null;
        if ($request->has('blocks')) {
            $blocks = $this->validator->validate(['blocks' => $request->input('blocks')])['blocks'];
        }

        $this->authoring->update($cbt, $request->meta(), $blocks);

        return response()->json($this->authoring->payload($cbt->refresh()));
    }

    /** DELETE /cbts/{cbt}: attempts and scores are kept; the questions go with the CBT. */
    public function destroy(Request $request, Cbt $cbt): JsonResponse
    {
        $this->authorizeHost($request, $cbt);

        $cbt->delete();

        return response()->json(['ok' => true]);
    }

    /** Only the person who set a CBT up may read its answer key or change it. */
    private function authorizeHost(Request $request, Cbt $cbt): void
    {
        $actor = $this->cbtActor($request);

        abort_unless(
            $cbt->host_type === $actor->getMorphClass() && (int) $cbt->host_id === (int) $actor->getKey(),
            403,
        );
    }
}
