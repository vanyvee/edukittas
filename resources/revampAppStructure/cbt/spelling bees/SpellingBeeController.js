use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SpellingController extends Controller
{
    public function checkSpelling(Request $request)
    {
        $expected = strtolower($request->expected); // e.g., 'girl'
        $audioFile = $request->file('audio');
        $duration = $request->duration; // time taken by player

        // --- Step 1: Send audio to Whisper ---
        $whisperResponse = Http::attach(
            'file', file_get_contents($audioFile), $audioFile->getClientOriginalName()
        )->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => 'whisper-1',
        ]);

        $text = strtolower($whisperResponse->json('text'));

        // --- Step 2: Check spelling correctness ---
        preg_match_all('/\b[a-z]\b/', $text, $letters);
        $spelledWord = implode('', $letters[0]);
        $isSpelling = strlen($spelledWord) >= strlen($expected);
        $isCorrect = $isSpelling && $spelledWord === $expected;

        // --- Step 3: If correct, request GPT for remark ---
        $remark = null;
        if ($isCorrect) {
            $prompt = "A child spelled the word '{$expected}' correctly in {$duration} seconds. 
            Write an exciting, positive, confidence-boosting message for the child.";

            $gptResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini', // or gpt-5 when available
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a cheerful tutor who praises kids.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 60,
            ]);

            $remark = trim($gptResponse->json('choices')[0]['message']['content']);
        }

        return response()->json([
            'word' => $expected,
            'is_correct' => $isCorrect,
            'spelled' => $spelledWord,
            'transcript' => $text,
            'remark' => $remark ?? 'Try again, you can do it!',
        ]);
    }
}








<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SpellingAIService;
use App\Models\GameSession;
use App\Models\Player;
use App\Models\SpellingAttempt;

class SpellingBeeController extends Controller
{
    protected $ai;

    public function __construct(SpellingAIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Initialize a multiplayer game session
     */
    public function startGame(Request $request)
    {
        $session = GameSession::create([
            'room_code' => strtoupper(uniqid('SB')),
            'remark_slots' => json_encode($this->generateRemarkSlots()), // 4 out of 10
        ]);

        return response()->json([
            'session_id' => $session->id,
            'room_code' => $session->room_code,
            'remark_slots' => json_decode($session->remark_slots)
        ]);
    }

    /**
     * Handle spelling submission from a player
     */
    public function submitSpell(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer',
            'player_id' => 'required|integer',
            'word' => 'required|string',
            'transcript' => 'required|string',
            'duration' => 'required|integer',
            'question_number' => 'required|integer',
            'word_difficulty' => 'required|integer',
        ]);
       // this is wrong..game session should 
        //have been in redis already at gane start
        //
        $session = GameSession::findOrFail($request->session_id);
        $player = Player::findOrFail($request->player_id);
        $remarkSlots = json_decode($session->remark_slots);

        // Evaluate spelling via AI
        $result = $this->ai->processAttempt(
            childId: $player->id,
            expectedWord: $request->word,
            transcript: $request->transcript,
            duration: $request->duration,
            playerGrade: $player->grade_level,
            wordDifficulty: $request->word_difficulty
        );

        $personalRemark = null;
        $genericRemark = null;

        // Only send remarks if this question number is selected
        if ($this->ai->shouldTriggerRemark($request->question_number, $remarkSlots)) {
            // PERSONAL REMARK (already inside $result)
            $personalRemark = $result['remark'];

            // GENERIC REMARK (based on room activity)
            $gameState = $this->buildGameStateSummary($session->id);
            $genericRemark = $this->ai->generateGenericRemark($gameState);
        }

        return response()->json([
            'correct' => $result['is_correct'],
            'spelled' => $result['spelled'],
            'personal_remark' => $personalRemark,
            'generic_remark' => $genericRemark,
        ]);
    }

    /**
     * End-of-game summary for one player
     */
    public function endGame(Request $request)
    {
        $player = Player::findOrFail($request->player_id);

        $finalStats = [
            'total_attempts' => SpellingAttempt::where('child_id', $player->id)->count(),
            'correct' => SpellingAttempt::where('child_id', $player->id)->where('is_correct', 1)->count(),
            'wrong' => SpellingAttempt::where('child_id', $player->id)->where('is_correct', 0)->count(),
            'fastest_time' => SpellingAttempt::where('child_id', $player->id)->min('duration'),
        ];

        $remark = $this->ai->finalSummaryRemark($player->id, $finalStats);

        return response()->json([
            'final_stats' => $finalStats,
            'summary_remark' => $remark
        ]);
    }

    /** Generate 4 random remark slots */
    private function generateRemarkSlots(): array
    {
        return collect(range(1, 10))->shuffle()->take(4)->values()->toArray();
    }

    /** Build room-wide game state summary */
    private function buildGameStateSummary(int $sessionId): array
    {
        $players = Player::where('session_id', $sessionId)->get();

        $state = [];
        foreach ($players as $p) {
            $state[] = [
                'player' => $p->name,
                'grade' => $p->grade_level,
                'correct' => SpellingAttempt::where('child_id', $p->id)->where('is_correct', 1)->count(),
                'wrong' => SpellingAttempt::where('child_id', $p->id)->where('is_correct', 0)->count(),
                'streak' => $this->calculateStreak($p->id),
            ];
        }

        return $state;
    }

    private function calculateStreak(int $childId): int
    {
        $attempts = SpellingAttempt::where('child_id', $childId)
            ->orderBy('id', 'desc')
            ->get();

        $streak = 0;
        foreach ($attempts as $a) {
            if ($a->is_correct) $streak++; else break;
        }
        return $streak;
    }
}

