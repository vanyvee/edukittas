<?php

namespace App\Http\Requests;

use App\Support\QuestionRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One request for both the bank (Question) and CBT drafts (TempQuestion).
 *
 * Standalone: { type, text, options, correct_answers, subject_id, class_id, topic_id }
 * Group:      { group: {title, content, type, introduction?},
 *               subject_id, class_id, topic_id,          // defaults for every child
 *               questions: [ {position, type, text, options, correct_answers}, ... ] }
 */
class StoreQuestionRequest extends FormRequest
{
    /** Access control lives in middleware/policies. Always true here. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Is this a group payload? Called by: rules(), prepareForValidation(), withValidator(),
     * and by your controller to decide how to save.
     * Returns: bool.
     */
    public function isGroup(): bool
    {
        return $this->has('group');
    }

    /**
     * Runs before validation. Cleans options/answers into strings; for a group,
     * copies root-level subject/class/topic/difficulty/marks into children that lack them.
     * Returns: nothing (modifies the request input).
     */
    protected function prepareForValidation(): void
    {
        if (! $this->isGroup()) {
            $this->merge(QuestionRules::normalize($this->only(['type', 'options', 'correct_answers'])));
            return;
        }

        $defaults  = array_filter(
            $this->only(['subject_id', 'class_id', 'topic_id', 'difficulty_level', 'marks']),
            fn ($v) => $v !== null
        );

        $this->merge(['questions' => array_map(
            fn ($q) => is_array($q) ? QuestionRules::normalize($defaults + $q) : $q,
            (array) $this->input('questions')
        )]);
    }

    /**
     * Validation rules. Laravel calls this automatically.
     * Returns: rules array (standalone question, or group + its questions).
     */
    public function rules(): array
    {
        if (! $this->isGroup()) {
            return QuestionRules::questionRules() + QuestionRules::classificationRules();
        }

        return QuestionRules::groupRules('group.') + [
            'questions'            => ['required', 'array', 'min:1'],
            'questions.*.position' => ['required_if:group.type,gap', 'nullable', 'integer', 'min:1'],
        ] + QuestionRules::questionRules('questions.*.')
          + QuestionRules::classificationRules('questions.*.');
    }

    /**
     * Extra checks after the basic rules pass: answers-must-be-options per question,
     * and gap markers vs positions for gap groups.
     * Laravel calls this automatically. Returns: nothing (adds errors to the validator).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->isGroup()) {
                $errors = QuestionRules::crossCheck($this->all());
            } else {
                $errors = QuestionRules::gapErrors($this->input('group'), $this->input('questions'));

                foreach ($this->input('questions') as $i => $q) {
                    foreach (QuestionRules::crossCheck($q) as $field => $message) {
                        $errors["questions.{$i}.{$field}"] = $message;
                    }
                }
            }

            foreach ($errors as $key => $message) {
                $v->errors()->add($key, $message);
            }
        });
    }
}
