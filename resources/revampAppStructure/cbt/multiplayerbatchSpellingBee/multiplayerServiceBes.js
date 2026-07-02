


<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\SpellingSession;
use App\Models\SpellingAttempt;
use OpenAI\OpenAI;

class SpellingAIService
{
    protected string $openai_key;

    public function __construct()
    {
        $this->openai_key = env('OPENAI_API_KEY');
    }

    /**
     * Handle a full spelling attempt lifecycle
     */
    public function processAttempt(int $childId, string $expectedWord, string $transcript, int $duration, int $playerGrade, int $wordDifficulty)
    {
        $gradeEquivalent = round($playerGrade / 1.5, 1);
        $hypeDelta = $wordDifficulty - $gradeEquivalent;

        preg_match_all('/\b[a-z]\b/', strtolower($transcript), $letters);
        $spelledWord = implode('', $letters[0]);

        $isSpelling = strlen($spelledWord) >= strlen($expectedWord);
        $isCorrect  = $isSpelling && ($spelledWord === strtolower($expectedWord));

        $attempt = SpellingAttempt::create([
            'child_id'     => $childId,
            'word'         => $expectedWord,
            'transcript'   => $transcript,
            'spelled_word' => $spelledWord,
            'is_correct'   => $isCorrect,
            'duration'     => $duration,
            'grade'        => $playerGrade,
            'difficulty'   => $wordDifficulty,
            'hype_delta'   => $hypeDelta,
        ]);

        return [
            'is_correct' => $isCorrect,
            'spelled'    => $spelledWord,
            'hype_delta' => $hypeDelta,
        ];
    }

    /**
     * Summarize child's progress
     */
    protected function summarizeProgress(int $childId): array
    {
        $attempts = SpellingAttempt::where('child_id', $childId)
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        $correct = $attempts->where('is_correct', true)->count();
        $wrong = $attempts->where('is_correct', false)->count();

        $recentWords = $attempts->take(5)->mapWithKeys(function ($a) {
            return [$a->word => $a->is_correct ? 'correct' : 'wrong'];
        });

        // compute streak
        $streak = 0;
        foreach ($attempts as $a) {
            if ($a->is_correct) $streak++; else break;
        }

        return [
            'total_attempts' => $attempts->count(),
            'correct' => $correct,
            'wrong' => $wrong,
            'recent_words' => $recentWords,
            'streak' => $streak,
        ];
    }

    /**
     * Generate friendly motivational remark via GPT
     */
    protected function generateRemark(int $childId, string $word, bool $isCorrect, int $duration, int $playerGrade, int $wordDifficulty, float $hypeDelta): string
    {
        $summary = $this->summarizeProgress($childId);

        $tone = 'balanced';
        if ($hypeDelta >= 2) $tone = 'hyper';        // child beat stronger word
        elseif ($hypeDelta >= 1) $tone = 'excited';  // slight challenge win
        elseif ($hypeDelta <= -2) $tone = 'calm';     // word far below level
        elseif ($hypeDelta <= -1) $tone = 'soft';     // mild under-level

        $prompt = "Player grade: {$playerGrade}. Word difficulty: {$wordDifficulty}. Hype delta: {$hypeDelta}. Tone: {$tone}.
" .
                  "Child progress summary: " . json_encode($summary) . ".
" .
                  "The child " . ($isCorrect ? "spelled '{$word}' correctly" : "missed '{$word}'") . " in {$duration} seconds.
" .
                  "Write a short remark using the tone specified. Keep child-friendly energy.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openai_key,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You generate motivational remarks for students based on performance and word difficulty.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 80,
        ]);

        return trim($response->json('choices')[0]['message']['content'] ?? 'Great effort! Keep going!');
    }
    /**
     * Decide if this question index should trigger remarks (4 out of 10)
     */
    public function shouldTriggerRemark(int $questionNumber, array $remarkSlots): bool
    {
        return in_array($questionNumber, $remarkSlots);
    }


    /**
     * End-of-game summary remark
     */
    public function finalSummaryRemark(int $childId, array $finalStats): string
    {
        $progress = $this->summarizeProgress($childId);

        $prompt = "Child final performance: " . json_encode($finalStats) . ".
" .
                  "Progress summary: " . json_encode($progress) . ".
" .
                  "Write a warm, motivational end-of-game conclusion remark.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openai_key,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You provide end-of-game praise for students.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 80,
        ]);

        return trim($response->json('choices')[0]['message']['content'] ?? 'Fantastic effort today! You did great!');
    }

    /**
     * Difficulty-based word bank categorization helper (1–8 scale)
     */
    public function categorizeWord(string $word, int $difficulty): array
    {
        return [
            'word' => $word,
            'difficulty' => $difficulty,
            'tier' => $difficulty <= 2 ? 'easy' : ($difficulty <= 5 ? 'medium' : 'hard'),
        ];
    }
}





/**
 * Batch generate personal remarks for multiple players + one generic remark
 */
public function generateBatchRemarks(array $playersData, array $gameState): array
{
    // playersData example:
    // [
    //   [
    //     'player_id' => 4,
    //     'player_name' => 'Mariam',
    //     'grade' => 7,
    //     'word' => 'banana',
    //     'difficulty' => 8,
    //     'hype_delta' => +3,
    //     'is_correct' => true,
    //     'duration' => 2.3
    //   ],
    //   ...
    // ]

    $prompt = "You are generating personalized remarks for multiple students in a multiplayer spelling game.\n"
            . "Here is each player's performance summary: " . json_encode($playersData) . ".\n"
            . "Here is the overall game state for the room: " . json_encode($gameState) . ".\n"
            . "Return EXACTLY this JSON structure:\n"
            . "{ \"player_remarks\": {playerId: remarkText}, \"generic_remark\": text }";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->openai_key,
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => 'You generate concise motivational remarks for students based on performance.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'max_tokens' => 200,
    ]);

    $json = $response->json('choices')[0]['message']['content'] ?? '{}';
    $parsed = json_decode($json, true);

    return [
        'player_remarks' => $parsed['player_remarks'] ?? [],
        'generic_remark' => $parsed['generic_remark'] ?? 'The competition is heating up!',
    ];
}



/**
 * Build a map of latest attempt per player with streaks
 * 
 * Returns:
 * [
 *   player_id => [
 *      'word' => 'apple',
 *      'difficulty' => 5,
 *      'hype_delta' => 1.5,
 *      'is_correct' => true,
 *      'duration' => 3.2,
 *      'streak' => 2
 *   ],
 *   ...
 * ]
 */
public function getLatestAttemptsMap(array $players): array
{
    $map = [];

    foreach ($players as $p) {
        // Get latest attempt for this player
        $latest = SpellingAttempt::where('child_id', $p->id)
                    ->orderBy('id', 'desc')
                    ->first();

        if (!$latest) continue;

        // Compute current streak
        $streak = 0;
        $attempts = SpellingAttempt::where('child_id', $p->id)
                        ->orderBy('id', 'desc')
                        ->get();

        foreach ($attempts as $a) {
            if ($a->is_correct) $streak++;
            else break;
        }

        $map[$p->id] = [
            'word'       => $latest->word,
            'difficulty' => $latest->difficulty,
            'hype_delta' => $latest->hype_delta,
            'is_correct' => $latest->is_correct,
            'duration'   => $latest->duration,
            'streak'     => $streak,
        ];
    }

    return $map;
}



/**
 * Prepare batch player data from latest spelling attempts
 */
/**
 * Build full batch player data including streaks for AI remark generation
 *
 * Input:
 *  - $players: array of Player models
 *  - $latestAttempts: map of latest attempts per player from getLatestAttemptsMap()
 *
 * Output:
 * [³
 *   [
 *      'player_id'  => 4,
 *      'player_name'=> 'Mariam',
 *      'grade'      => 7,
 *      'word'       => 'banana',
 *      'difficulty' => 8,
 *      'hype_delta' => 3,
 *      'is_correct' => true,
 *      'duration'   => 2.3,
 *      'streak'     => 2
 *   ],
 *   ...
 * ]
 */
public function buildBatchPlayerData(array $players, array $latestAttempts): array
{
    $data = [];

    foreach ($players as $p) {
        if (!isset($latestAttempts[$p->id])) continue;

        $a = $latestAttempts[$p->id];

        $data[] = [
            'player_id'   => $p->id,
            'player_name' => $p->name,
            'grade'       => $p->grade_level,
            'word'        => $a['word'],
            'difficulty'  => $a['difficulty'],
            'hype_delta'  => $a['hype_delta'],
            'is_correct'  => $a['is_correct'],
            'duration'    => $a['duration'],
            'streak'      => $a['streak'],
        ];
    }

    return $data;
}
public function generateTTS(string $text): string
{
    $client = new OpenAI(env('OPENAI_API_KEY'));

    // Generate WAV bytes
    $response = $client->audio()->speak([
        'model' => 'gpt-4o-mini-tts',
        'voice' => 'alloy',
        'format' => 'wav',
        'input' => $text,
    ]);

    // OpenAI SDK returns bytes in 'data'
    return $response->getContents(); // raw WAV bytes
}

/**
 * Generate remark slots based on total questions and admin percentage
 *
 * @param int $totalQuestions
 * @param int|null $adminPercentage Admin input (0-100), null = default 40%
 * @return array Randomly selected question numbers for remarks
 */
private function generateRemarkSlots(int $totalQuestions, ?int $adminPercentage = null): array
{
    if ($totalQuestions <= 0) {
        return [];
    }

    // Determine percentage
    $percentage = $adminPercentage ?? 40;      // default 40%
    $percentage = max(0, min($percentage, 70)); // cap at 70%

    // 0% → no remarks
    if ($percentage === 0) {
        return [];
    }

    // Compute number of remark slots (round down)
    $numRemarks = floor($totalQuestions * ($percentage / 100));

    // Ensure at least 1 remark if percentage > 0 and totalQuestions >= 1
    $numRemarks = max(1, $numRemarks);

    // Randomly select question numbers
    return collect(range(1, $totalQuestions))
        ->shuffle()
        ->take($numRemarks)
        ->values()
        ->toArray();
}


}


