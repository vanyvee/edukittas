<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Single source of truth for question validation.
 * Used by StoreQuestionRequest (forms) and ValidatesMarkable (model saves).
 */
class QuestionRules
{
    /**
     * Basic rules for one question.
     * Called by: the request (standalone and group children) and the model trait.
     * $p = key prefix, e.g. 'questions.*.' for children inside a group.
     * Returns: Laravel rules array.
     */
    public static function questionRules(string $p = ''): array
    {
        return [
            "{$p}type"              => ['required', Rule::in(['objective', 'subjective'])],
            "{$p}text"              => ['required', 'string'],
            "{$p}marks"             => ['nullable', 'integer', 'between:1,255'],
            "{$p}difficulty_level"  => ['nullable', Rule::in(['substandard', 'standard', 'advanced'])],
            "{$p}options"           => ["required_if:{$p}type,objective", 'nullable', 'array', 'min:2'],
            "{$p}options.*"         => ['required', 'string'],
            "{$p}correct_answers"   => ['required', 'array', 'min:1'],
            "{$p}correct_answers.*" => ['required', 'string'],
        ];
    }

    /**
     * Subject / class / topic must exist.
     * Called by: the request only. The model layer leaves this to the DB foreign keys.
     * Returns: Laravel rules array.
     */
    public static function classificationRules(string $p = ''): array
    {
        return [
            "{$p}subject_id" => ['required', 'integer', 'exists:subjects,id'],
            "{$p}class_id"   => ['required', 'integer', 'exists:my_classes,id'],
            "{$p}topic_id"   => ['required', 'integer', 'exists:subject_topics,id'],
        ];
    }

    /**
     * Basic rules for the group (passage) itself.
     * Called by: the request when a `group` key is sent.
     * Returns: Laravel rules array.
     */
    public static function groupRules(string $p = ''): array
    {
        return [
            "{$p}title"        => ['required', 'string', 'max:255'],
            "{$p}introduction" => ['nullable', 'string'],
            "{$p}content"      => ['required', 'string'],
            "{$p}type"         => ['required', Rule::in(['gap', 'passage', 'diagram', 'audio'])],
        ];
    }

    /**
     * Cleans one question's input before validation:
     * numbers in options/correct_answers become strings (storage format),
     * and an empty options list on a subjective question becomes null.
     * Called by: the request (prepareForValidation) and the model trait.
     * Returns: the cleaned question array.
     */
    public static function normalize(array $q): array
    {
        foreach (['options', 'correct_answers'] as $key) {
            if (isset($q[$key]) && is_array($q[$key])) {
                $q[$key] = array_values(array_map(fn ($v) => is_scalar($v) ? (string) $v : $v, $q[$key]));
            }
        }

        if (($q['type'] ?? null) === 'subjective' && ($q['options'] ?? null) === []) {
            $q['options'] = null;
        }

        return $q;
    }

    /**
     * Checks that the built-in rules can't do safely:
     * objective options are unique and every correct answer is one of them;
     * subjective questions carry no options.
     * Called by: the request and the model trait, only AFTER questionRules() passed.
     * Returns: [field => message]. Empty array = fine.
     */
    public static function crossCheck(array $q): array
    {
        $options = array_map('strval', (array) ($q['options'] ?? []));

        if (($q['type'] ?? null) === 'subjective') {
            return $options ? ['options' => 'Subjective questions must not have options.'] : [];
        }

        if (count($options) !== count(array_unique($options))) {
            return ['options' => 'Options must be unique.'];
        }

        foreach ((array) ($q['correct_answers'] ?? []) as $answer) {
            if (! in_array((string) $answer, $options, true)) {
                return ['correct_answers' => "Correct answer \"{$answer}\" is not one of the options."];
            }
        }

        return [];
    }

    /**
     * The only group rule that matters: in a gap passage, the {{n}} markers
     * and the questions' positions must match exactly.
     * Called by: the request, after basic rules pass, when a group is sent.
     * Returns: [errorKey => message]. Empty array = fine (always empty for non-gap groups).
     */
    public static function gapErrors(array $group, array $questions): array
    {
        if (($group['type'] ?? null) !== 'gap') {
            return [];
        }

        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', (string) $group['content'], $m);
        $markers   = array_unique(array_map('intval', $m[1]));
        $positions = array_map('intval', array_column($questions, 'position'));

        $errors = [];

        if ($missing = array_diff($markers, $positions)) {
            $errors['group.content'] = 'These gaps have no question: ' . implode(', ', $missing) . '.';
        }
        if ($extra = array_diff($positions, $markers)) {
            $errors['questions'] = 'These positions have no gap in the passage: ' . implode(', ', $extra) . '.';
        }

        return $errors;
    }
}
