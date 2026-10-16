<?php

namespace App\Services;

use App\Models\Word;
use Illuminate\Support\Facades\Http;

class WordService
{
    public static function processWords(array $words)
    {
        $results = [];

        foreach ($words as $word) {

            $original = $word;

            // ✅ 1. Correct Spelling
            $corrected = self::correctSpelling($word, $requiresReview);

            // Step 1b: Generate audio URL
           $audio_url = self::generateAudioUrl($corrected);
            // Step 1b: Fetch meaning & sample
           $dictData = self::fetchFromDictionaryApi($corrected);

           if (!$dictData) {
           $dictData = self::fetchFromAi($corrected); // fallback
           }

            $meaning =  self::censorWord($corrected, $dictData['meaning']);
            $sample_sentence = self::censorWord($corrected,$dictData['sample_sentence']) ?? null;
            
            // ✅ 2. Calculate Scores
            $phonetic       = self::phoneticScore($corrected);
            $frequency      = self::frequencyScore($corrected);
            $morphology     = self::morphologyScore($corrected);
            $letter_pattern = self::letterPatternScore($corrected);

            // ✅ 3. Length modifier
            $len = strlen($corrected);
            $length_modifier = ($len > 12) ? 2 : (($len > 8) ? 1 : 0);

            // ✅ 4. Total score
            $total_score = $phonetic + $frequency + $morphology + $letter_pattern + $length_modifier;

            // ✅ 5. Difficulty
            $difficulty = self::mapScoreToDifficulty($total_score);

            // ✅ 6. Grade + Age bracket
            $gradeAge = self::mapDifficultyToGradeAndAge($difficulty);
            $grade_bracket = $gradeAge['grade_bracket'];
            $age_bracket   = $gradeAge['age_bracket'];

            // ✅ 7. AI meaning, sentence & audio
            $ai = self::fetchWordAIData($corrected);

            $meaning   = $ai['meaning'] ?? null;
            $sentence  = self::censorWord($corrected, $ai['sentence'] ?? null);
            $audio_url = $ai['audio_url'] ?? null;

            // ✅ 8. Store in DB
            $wordModel = Word::updateOrCreate(
                ['word' => $corrected],
                [
                    'phonetic'       => $phonetic,
                    'frequency'      => $frequency,
                    'morphology'     => $morphology,
                    'letter_pattern' => $letter_pattern,
                    'length_modifier'=> $length_modifier,
                    'total_score'    => $total_score,
                    'difficulty'     => $difficulty,
                    'grade_bracket'  => $grade_bracket,
                    'age_bracket'    => $age_bracket,
                    'meaning'        => $meaning,
                    'sentence'       => $sentence,
                    'audio_url'      => $audio_url
                ]
            );

            $results[] = [
                'original'      => $original,
                'corrected'     => $corrected,
                'requires_review'=> $requiresReview,
                'difficulty'    => $difficulty,
                'grade_bracket' => $grade_bracket,
                'age_bracket'   => $age_bracket,
                'meaning'       => $meaning,
                'sentence'      => $sentence,
                'audio_url'     => $audio_url
            ];
        }

        return $results;
    }

    // ----------------------------------------------------
    // ✅ SPELLING CORRECTION
    // ----------------------------------------------------
    private static function correctSpelling($word, &$requiresReview = false)
    {
        $wordLower = strtolower($word);

        if (!extension_loaded('enchant')) {
            $requiresReview = false;
            return $wordLower;
        }

        $broker = enchant_broker_init();
        $dict   = enchant_broker_request_dict($broker, "en_US");

        if (enchant_dict_check($dict, $wordLower)) {
            enchant_broker_free_dict($dict);
            enchant_broker_free($broker);
            return $wordLower;
        }

        $suggestions = enchant_dict_suggest($dict, $wordLower);

        enchant_broker_free_dict($dict);
        enchant_broker_free($broker);

        if (!empty($suggestions)) {
            return strtolower($suggestions[0]);
        }

        $requiresReview = true;
        return $wordLower;
    }

    // ----------------------------------------------------
    // ✅ SCORING
    // ----------------------------------------------------
    private static function phoneticScore($word)
    {
        $patterns = ['mn','kn','ph','gh','rh','cwr','pt'];
        $score = 0;
        foreach ($patterns as $p) {
            if (stripos($word, $p) !== false) $score++;
        }
        return min($score, 5);
    }

    private static function frequencyScore($word)
    {
        $common = ['apple','table','chair','water','teacher'];
        return in_array($word, $common) ? 0 : 5;
    }

    private static function morphologyScore($word)
    {
        $fx = ['ique','eau','ology','phobia','freude'];
        foreach ($fx as $s) {
            if (str_ends_with($word, $s)) return 3;
        }
        return 0;
    }

    private static function letterPatternScore($word)
    {
        $patterns = ['ei','ie','ough','augh','ph','ght','pt','mn','cwr'];
        $score = 0;
        foreach ($patterns as $p) {
            if (stripos($word, $p) !== false) $score++;
        }
        return min($score, 5);
    }

    private static function mapScoreToDifficulty($score)
    {
        return match (true) {
            $score <= 5  => 'Easy',
            $score <= 10 => 'Medium',
            $score <= 16 => 'Hard',
            default      => 'Expert'
        };
    }

    private static function mapDifficultyToGradeAndAge($difficulty)
    {
        return match ($difficulty) {
            'Easy' => [
                'grade_bracket' => 'Grade 1–3',
                'age_bracket'   => '6–8 years'
            ],
            'Medium' => [
                'grade_bracket' => 'Grade 4–7',
                'age_bracket'   => '11–14 years'
            ],
            'Hard' => [
                'grade_bracket' => 'Grade 8–12',
                'age_bracket'   => '14–18 years'
            ],
            'Expert' => [
                'grade_bracket' => 'College Level',
                'age_bracket'   => '18+ years'
            ],
        };
    }

    // ----------------------------------------------------
    // ✅ AI LOOKUPS (Meaning + Sentence + Audio URL)
    // ----------------------------------------------------
    private static function fetchFromAi($word)
    {
        // ✅ One place to change if you change AI provider
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('AI_API_KEY'),
            ])->post(env('AI_ENDPOINT'), [
                'prompt' => "Give me a JSON response for the word '$word' with keys:
                meaning, sentence.
                The sentence MUST include the word naturally.",
            ]);

            if ($response->failed()) return [];

            return $response->json();

        } catch (\Exception $e) {
            return [];
        }
    }

    // ----------------------------------------------------
    // ✅ CENSOR SENTENCE
    // ----------------------------------------------------
    private static function censorWord($word, $sentence)
    {
        if (!$sentence) return null;

        return preg_replace("/\b$word\b/i", "*****", $sentence);
    }
    
    
    
    private static function generateAudioUrl($word)
{
    // Convert word to lowercase and replace spaces with hyphens (basic sanitization)
    $cleanWord = strtolower(str_replace(' ', '-', $word));

    // Construct the audio URL
    return "https://api.dictionaryapi.dev/media/pronunciations/en/{$cleanWord}-us.mp3";
}
    
    private static function fetchFromDictionaryApi($word)
{
    $url = "https://api.dictionaryapi.dev/api/v2/entries/en/{$word}";

    try {
        $response = file_get_contents($url);
        if (!$response) return null;

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data)) return null;

        // Take first meaning and first definition
        $meaning = $data[0]['meanings'][0]['definitions'][0]['definition'] ?? null;
        $example = $data[0]['meanings'][0]['definitions'][0]['example'] ?? null;

        return [
            'meaning' => $meaning,
            'sample_sentence' => $example
        ];

    } catch (\Exception $e) {
        return null;
    }
}
    
    
    
    
}