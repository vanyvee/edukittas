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





    public function setWordsOnDB(Request $request)
{
    $request->validate([
        'words' => 'required|array|min:1',
        'words.*' => 'string|min:1'
    ]);

    $processed = \App\Services\WordService::processWords($request->words);

    return response()->json([
        'message' => 'Words processed and stored.',
        'data' => $processed
         ]);
}
}



