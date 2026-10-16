


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SpellingAIService;
use App\Models\GameSession;
use App\Models\Player;
use App\Models\SpellingAttempt;
use Illuminate\Support\Facades\Cache;

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
        'session_id'      => 'required|integer',
        'player_id'       => 'required|integer',
        'word'            => 'required|string',
        'transcript'      => 'required|string',
        'duration'        => 'required|integer',
        'question_number' => 'required|integer',
        'word_difficulty' => 'required|integer',
    ]);

    $session = GameSession::findOrFail($request->session_id);
    $player  = Player::findOrFail($request->player_id);
    $remarkSlots = json_decode($session->remark_slots, true);

    // ---- 1) Evaluate & store attempt ----
    $result = $this->ai->processAttempt(
        childId:        $player->id,
        expectedWord:   $request->word,
        transcript:     $request->transcript,
        duration:       $request->duration,
        playerGrade:    $player->grade_level,
        wordDifficulty: $request->word_difficulty
    );

    // --------- 2) REDIS submission tracker ----------
    $roundKey = "sb:{$session->id}:q{$request->question_number}:submitted";
    Redis::hset($roundKey, $player->id, 1);

    $submittedCount = Redis::hlen($roundKey);
    $playerCount    = Player::where('session_id', $session->id)->count();
    $allSubmitted = ($submittedCount >= $playerCount);

    $personalRemark = null;
    $genericRemark  = null;

    // ---- 3) Handle remark generation only when last player submits ----
    if ($this->ai->shouldTriggerRemark($request->question_number, $remarkSlots) && $allSubmitted) {

        // Build batch payloads
        $players        = Player::where('session_id', $session->id)->get();
        $latestAttempts = $this->ai->getLatestAttemptsMap($players);
        $playersData    = $this->ai->buildBatchPlayerData($players->all(), $latestAttempts);
        $gameState      = $this->buildGameStateSummary($session->id);

        // Generate all remarks ONCE
        $remarks = $this->ai->generateBatchRemarks($playersData, $gameState);

        // Grab this player's personal remark NOW
        $personalRemark = $remarks['player_remarks'][$player->id] ?? null;
        $genericRemark  = $remarks['generic_remark'] ?? null;

        // ---- 3a) GENERIC REMARK TTS ----
        if ($genericRemark) {
            // 1) Generate WAV → Opus in-memory
            $wavBlob  = $this->ai->generateTTS($genericRemark); // raw bytes
            $opusBlob = $this->convertWavToOpus($wavBlob, 64_000); // 64 kbps

            // 2) Store in Redis (2 min TTL)
            $audioKey = "sb:tts:session:{$session->id}:q{$request->question_number}:generic";
            Redis::setex($audioKey, 120, base64_encode($opusBlob));

            // 3) Attach audio key to remarks for broadcast
            $remarks['generic_audio_key'] = $audioKey;
        }

        // --------------------- 4) BROADCAST ----------------------
        broadcast(new SpellingBeeRoundComplete(
            sessionId: $session->id,
            remarks:   $remarks,
            question:  $request->question_number
        ));

        // --------------------- 5) Clear Redis keys immediately ---------------------
        Redis::del($roundKey);
    }

    return response()->json([
        'correct'         => $result['is_correct'],
        'spelled'         => $result['spelled'],
        'personal_remark' => $personalRemark,
        'generic_remark'  => $genericRemark,
    ]);
}
     


public function fetchTTSBlob(string $key)
{
    $base64 = Redis::get($key);

    if (!$base64) {
        return response()->json(['error' => 'Audio not found or expired'], 404);
    }

    $audioBytes = base64_decode($base64);

    return response($audioBytes, 200)
        ->header('Content-Type', 'audio/opus')
        ->header('Content-Disposition', 'inline; filename="remark.opus"');
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


}


