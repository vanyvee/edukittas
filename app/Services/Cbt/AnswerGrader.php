<?php

namespace App\Services\Cbt;

/**
 * Pure marking rules. No database, no models, so it is trivial to unit test.
 *
 * A question with no options is subjective; one with options is objective.
 * Marking is all or nothing (no partial credit for multi-select).
 */
final class AnswerGrader
{
    /**
     * @param  string|array<int,string>|null  $given     what the student submitted
     * @param  array<int,string>|null         $options   the question's options (null = subjective)
     * @param  array<int,string>              $accepted  the correct answers / accepted variants
     */
    public function isCorrect(string|array|null $given, ?array $options, array $accepted): bool
    {
        if ($given === null || $given === '' || $given === []) {
            return false;
        }

        return $options === null
            ? $this->subjective($given, $accepted)
            : $this->objective($given, $accepted);
    }

    /** Objective: the submitted option string(s) must equal the correct set exactly, in any order. */
    private function objective(string|array $given, array $accepted): bool
    {
        return $this->asSet(is_array($given) ? $given : [$given]) === $this->asSet($accepted);
    }

    /** Subjective: typed text must match any accepted answer after light clean-up. */
    private function subjective(string|array $given, array $accepted): bool
    {
        if (is_array($given)) {
            return false;
        }

        $typed = $this->normalize($given);
        if ($typed === '') {
            return false;
        }

        foreach ($accepted as $candidate) {
            if ($typed === $this->normalize((string) $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Case-insensitive, whitespace-collapsed, with punctuation trimmed from the ends only.
     * Internal punctuation is kept on purpose so "3.5" never equals "35"; a leading minus
     * before a digit is kept so "-5" never equals "5". Teachers add variants to the accepted list.
     */
    public function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/^(?:(?!-\d)[\p{P}\p{S}\s])+/u', '', $text) ?? $text;
        $text = preg_replace('/[\p{P}\p{S}\s]+$/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /** @return array<int,string> trimmed, unique, sorted */
    private function asSet(array $items): array
    {
        $items = array_values(array_unique(array_map(fn ($v) => trim((string) $v), $items)));
        sort($items, SORT_STRING);

        return $items;
    }
}
