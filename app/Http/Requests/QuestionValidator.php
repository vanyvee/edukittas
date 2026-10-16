<?php

namespace App\Services\Cbt;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * One set of rules for every way a question enters a CBT: typed in by hand, or
 * pulled from the bank into the panel and edited. Nothing is relaxed because the
 * question first came from the backend.
 *
 * Input:  ['blocks' => [
 *   ['kind' => 'question', 'question' => {...}],
 *   ['kind' => 'group', 'group' => {type,title,introduction,content}, 'questions' => [{...}]],
 * ]]
 * where each question is {text, options|null, correct_answers[], marks?, subject_id,
 * class_id, topic_id, difficulty_level?}.
 *
 * Output: the same blocks, cleaned: strings trimmed, `--` turned into {{n}} tokens,
 * `type` derived from options, positions assigned, and a content_hash on every question.
 * Throws Illuminate\Validation\ValidationException with errors keyed like
 * "blocks.2.questions.1.options" so the panel can point at the exact field.
 *
 * Not done here: checking a hash against the question bank. That belongs to the bank actions.
 */
final class QuestionValidator
{
    private const MAX_BLOCKS = 200;
    private const GROUP_TYPES = ['gap', 'passage', 'diagram', 'audio', 'video'];
    private const DIFFICULTIES = ['substandard', 'standard', 'advanced'];

    // Mirrors the frontend's media detection (see utils/content.js).
    private const MEDIA = '/^(data:(image|audio)\/\S+|\S+\.(png|jpe?g|gif|webp|avif|svg|mp3|wav|ogg|m4a|aac)(\?\S*)?)$/i';

    /** @return array{blocks: array} */
    public function validate(array $payload): array
    {
        $normalized = [];

        $validator = Validator::make($payload, $this->rules($payload));
        $validator->after(function ($v) use ($payload, &$normalized) {
            if ($v->errors()->isNotEmpty()) {
                return; // the shape must be sound before the deeper checks run
            }
            $normalized = $this->normalize($payload['blocks'], fn (string $key, string $msg) => $v->errors()->add($key, $msg));
        });
        $validator->validate();

        return ['blocks' => $normalized];
    }

    // ------------------------------------------------------------------
    // Shape rules (Laravel validator)
    // ------------------------------------------------------------------

    private function rules(array $payload): array
    {
        $rules = [
            'blocks' => ['required', 'array', 'min:1', 'max:' . self::MAX_BLOCKS],
            'blocks.*.kind' => ['required', Rule::in(['question', 'group'])],
        ];

        foreach (($payload['blocks'] ?? []) as $i => $block) {
            if (! is_array($block)) {
                continue;
            }
            $kind = $block['kind'] ?? null;

            if ($kind === 'question') {
                $rules["blocks.$i.question"] = ['required', 'array'];
                $rules += $this->questionRules("blocks.$i.question");
            } elseif ($kind === 'group') {
                $rules["blocks.$i.group"] = ['required', 'array'];
                $rules["blocks.$i.group.type"] = ['required', Rule::in(self::GROUP_TYPES)];
                $rules["blocks.$i.group.title"] = ['required', 'string', 'max:255'];
                $rules["blocks.$i.group.introduction"] = ['nullable', 'string', 'max:2000'];
                $rules["blocks.$i.group.content"] = ['required', 'string', 'max:20000'];
                $rules["blocks.$i.questions"] = ['required', 'array', 'min:1', 'max:100'];

                foreach (array_keys(is_array($block['questions'] ?? null) ? $block['questions'] : []) as $j) {
                    $rules["blocks.$i.questions.$j"] = ['required', 'array'];
                    $rules += $this->questionRules("blocks.$i.questions.$j");
                }
            }
        }

        return $rules;
    }

    private function questionRules(string $p): array
    {
        return [
            "$p.text" => ['nullable', 'string', 'max:5000'],
            "$p.options" => ['nullable', 'array', 'min:2', 'max:10'],
            "$p.options.*" => ['required', 'string', 'max:2000'],
            "$p.correct_answers" => ['required', 'array', 'min:1', 'max:10'],
            "$p.correct_answers.*" => ['required', 'string', 'max:2000'],
            "$p.marks" => ['sometimes', 'integer', 'min:1', 'max:20'],
            "$p.subject_id" => ['required', 'integer', 'exists:subjects,id'],
            "$p.class_id" => ['required', 'integer', 'exists:my_classes,id'],
            "$p.topic_id" => ['required', 'integer', 'exists:subject_topics,id'],
            "$p.difficulty_level" => ['sometimes', Rule::in(self::DIFFICULTIES)],
        ];
    }

    // ------------------------------------------------------------------
    // Cross-field checks and clean-up (pure PHP, no framework needed)
    // ------------------------------------------------------------------

    /**
     * @param  callable(string,string):void  $fail  records an error against a field key
     */
    public function normalize(array $blocks, callable $fail): array
    {
        $out = [];
        $seen = [];

        foreach ($blocks as $i => $block) {
            $base = "blocks.$i";

            if (($block['kind'] ?? null) === 'group') {
                $out[] = $this->normalizeGroup($block, $base, $fail, $seen);
                continue;
            }

            $q = $this->normalizeQuestion($block['question'], "$base.question", $fail, false, null, null);
            $this->rememberHash($q, "$base.question.text", $fail, $seen);
            $out[] = ['kind' => 'question', 'question' => $q];
        }

        return $out;
    }

    private function normalizeGroup(array $block, string $base, callable $fail, array &$seen): array
    {
        $g = $block['group'];
        $isGap = $g['type'] === 'gap';
        $content = trim($g['content']);
        $children = array_values($block['questions']);

        if ($isGap) {
            [$content, $gaps] = $this->tokenizeGaps($content, "$base.group.content", $fail);
            if ($gaps !== count($children)) {
                $fail("$base.questions", sprintf('This passage has %d gap(s) but %d question(s). They must match.', $gaps, count($children)));
            }
        }

        $questions = [];
        foreach ($children as $j => $child) {
            $position = $j + 1;
            $path = "$base.questions.$j";

            // A gap's own text is blank; the passage carries the sentence.
            $q = $this->normalizeQuestion($child, $path, $fail, $isGap, $content, $position);

            if ($isGap) {
                if ($q['options'] === null) {
                    $fail("$path.options", 'A gap needs options to choose from.');
                } elseif ($this->hasMedia($q['options'])) {
                    $fail("$path.options", 'Gap options must be plain text, not images or sound.');
                }
            }

            $this->rememberHash($q, "$path.text", $fail, $seen);
            $q['position'] = $position;
            $questions[] = $q;
        }

        $intro = isset($g['introduction']) ? trim((string) $g['introduction']) : '';

        return [
            'kind' => 'group',
            'group' => [
                'type' => $g['type'],
                'title' => trim($g['title']),
                'introduction' => $intro === '' ? null : $intro,
                'content' => $content,
            ],
            'questions' => $questions,
        ];
    }

    private function normalizeQuestion(
        array $q,
        string $path,
        callable $fail,
        bool $textOptional,
        ?string $groupContent,
        ?int $position,
    ): array {
        $text = trim((string) ($q['text'] ?? ''));
        if ($text === '' && ! $textOptional) {
            $fail("$path.text", 'Write the question text.');
        }

        $options = null;
        if (isset($q['options'])) {
            $options = array_values(array_map(fn ($o) => trim((string) $o), $q['options']));
            if (count(array_unique($options)) !== count($options)) {
                $fail("$path.options", 'Each option must be different.');
            }
        }

        $answers = array_values(array_unique(array_map(fn ($a) => trim((string) $a), $q['correct_answers'])));

        if ($options !== null) {
            foreach ($answers as $answer) {
                if (! in_array($answer, $options, true)) {
                    $fail("$path.correct_answers", 'Every correct answer must be one of the options.');
                    break;
                }
            }
            if (count($answers) >= count($options)) {
                $fail("$path.correct_answers", 'At least one option must be wrong.');
            }
        }

        return [
            'position' => null,
            'text' => $text,
            'options' => $options,                         // null = subjective
            'correct_answers' => $answers,
            'type' => $options === null ? 'subjective' : 'objective',
            'marks' => (int) ($q['marks'] ?? 1),
            'subject_id' => (int) $q['subject_id'],
            'class_id' => (int) $q['class_id'],
            'topic_id' => (int) $q['topic_id'],
            'difficulty_level' => $q['difficulty_level'] ?? 'standard',
            'content_hash' => self::hashFor($text, $options, $answers, $groupContent, $position),
        ];
    }

    /**
     * Accepts `--` (typed) or {{n}} (already tokenised), not both. Returns the content with
     * {{1}}, {{2}}... and the number of gaps.
     *
     * @return array{0:string,1:int}
     */
    private function tokenizeGaps(string $content, string $path, callable $fail): array
    {
        $tokenCount = preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $content, $m);
        $dashCount = preg_match_all('/(?<!-)--(?!-)/', $content);

        if ($tokenCount && $dashCount) {
            $fail($path, 'Use either -- or {{n}} to mark gaps, not both.');

            return [$content, $tokenCount];
        }

        if ($tokenCount) {
            $numbers = array_map('intval', $m[1]);
            if ($numbers !== range(1, $tokenCount)) {
                $fail($path, 'Gaps must be numbered 1, 2, 3 in order.');
            }

            return [preg_replace('/\{\{\s*(\d+)\s*\}\}/', '{{$1}}', $content), $tokenCount];
        }

        if ($dashCount === 0) {
            $fail($path, 'Add at least one gap, written as --');

            return [$content, 0];
        }

        $n = 0;
        $tokenised = preg_replace_callback('/(?<!-)--(?!-)/', function () use (&$n) {
            return '{{' . (++$n) . '}}';
        }, $content);

        return [$tokenised, $n];
    }

    // ------------------------------------------------------------------
    // Duplicates and hashing
    // ------------------------------------------------------------------

    /**
     * Verbatim identity: same text, same option set, same answers. For a gap or a passage
     * question the passage and position are part of the identity, so two different passages
     * that happen to share options are not duplicates.
     */
    public static function hashFor(string $text, ?array $options, array $answers, ?string $groupContent = null, ?int $position = null): string
    {
        $sort = function (array $items): array {
            $items = array_values(array_map(fn ($v) => trim((string) $v), $items));
            sort($items, SORT_STRING);

            return $items;
        };

        return hash('sha256', json_encode([
            $groupContent,
            $position,
            trim($text),
            $options === null ? null : $sort($options),
            $sort($answers),
        ], JSON_UNESCAPED_UNICODE));
    }

    private function rememberHash(array $q, string $path, callable $fail, array &$seen): void
    {
        if (isset($seen[$q['content_hash']])) {
            $fail($path, 'This question appears more than once in this CBT.');
        }
        $seen[$q['content_hash']] = true;
    }

    private function hasMedia(array $options): bool
    {
        foreach ($options as $option) {
            if (preg_match(self::MEDIA, $option)) {
                return true;
            }
        }

        return false;
    }
}
