<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\SpellingSession;
use App\Models\SpellingAttempt;

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
        // Normalize grade to difficulty scale (1–8)
        $gradeEquivalent = round($playerGrade / 1.5, 1);
        $hypeDelta = $wordDifficulty - $gradeEquivalent;

        preg_match_all('/\b[a-z]\b/', strtolower($transcript), $letters);
        $spelledWord = implode('', $letters[0]);

        $isSpelling = strlen($spelledWord) >= strlen($expectedWord);
        $isCorrect = $isSpelling && ($spelledWord === strtolower($expectedWord));

        $attempt = SpellingAttempt::create([
            'child_id' => $childId,
            'word' => $expectedWord,
            'transcript' => $transcript,
            'spelled_word' => $spelledWord,
            'is_correct' => $isCorrect,
            'duration' => $duration,
            'grade' => $playerGrade,
            'difficulty' => $wordDifficulty,
            'hype_delta' => $hypeDelta,
        ]);

        $remark = $this->generateRemark($childId, $expectedWord, $isCorrect, $duration, $playerGrade, $wordDifficulty, $hypeDelta);

        return [
            'is_correct' => $isCorrect,
            'spelled' => $spelledWord,
            'remark' => $remark
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
}







<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\SpellingSession;
use App\Models\SpellingAttempt;

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
        // Normalize grade to difficulty scale (1–8)
        $gradeEquivalent = round($playerGrade / 1.5, 1);
        $hypeDelta = $wordDifficulty - $gradeEquivalent;

        preg_match_all('/\b[a-z]\b/', strtolower($transcript), $letters);
        $spelledWord = implode('', $letters[0]);

        $isSpelling = strlen($spelledWord) >= strlen($expectedWord);
        $isCorrect = $isSpelling && ($spelledWord === strtolower($expectedWord));

        $attempt = SpellingAttempt::create([
            'child_id' => $childId,
            'word' => $expectedWord,
            'transcript' => $transcript,
            'spelled_word' => $spelledWord,
            'is_correct' => $isCorrect,
            'duration' => $duration,
            'grade' => $playerGrade,
            'difficulty' => $wordDifficulty,
            'hype_delta' => $hypeDelta,
        ]);

        $remark = $this->generateRemark($childId, $expectedWord, $isCorrect, $duration, $playerGrade, $wordDifficulty, $hypeDelta);

        return [
            'is_correct' => $isCorrect,
            'spelled' => $spelledWord,
            'remark' => $remark
        ];
    }

    /**
     * Summarize child's progress
     */
     * check this method , currently it
     * seems to be work just for one child
     * and ask gpt why is it ordering by child id, and why take 10
     * if it is orddered in that manner so that 
     * it displays all data for that child fine
     * but try to tweak this so that it summarizs
     * for all player returning an array of arrays
     * meaning it will accept ids of players in the game
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
     * Generate generic multiplayer remark
     */
    public function generateGenericRemark(array $gameState): string
    {
        $prompt = "Game state: " . json_encode($gameState) . ".
" .
                  "Create an exciting, competitive remark about the multiplayer spelling round. Keep it fun, energetic, and non-toxic.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openai_key,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You generate hype commentary for multiplayer spelling games.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 60,
        ]);

        return trim($response->json('choices')[0]['message']['content'] ?? 'The game is heating up!');
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
 * Prepare batch player data from latest spelling attempts
 */
public function buildBatchPlayerData(array $players, array $latestAttempts): array
{
    $data = [];

    foreach ($players as $p) {
        if (!isset($latestAttempts[$p->id])) continue;

        $a = $latestAttempts[$p->id];

        $data[] = [
            'player_id' => $p->id,
            'player_name' => $p->name,
            'grade' => $p->grade_level,
            'word' => $a->word,
            'difficulty' => $a->difficulty,
            'hype_delta' => $a->hype_delta,
            'is_correct' => $a->is_correct,
            'duration' => $a->duration,
        ];
    }

    return $data;
}




When do you call batching?

In your controller, instead of calling:

$this->ai->generateGenericRemark()

$this->ai->processAttempt() individually for each remark round


You now call one:

$batchRemarks = $this->ai->generateBatchRemarks($playerData, $gameState);

This returns:

All personal remarks

1 generic remark
