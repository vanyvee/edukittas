<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\TempQuestion;
use App\Models\TempQuestionGroup;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pushes volatile temp questions/groups into the permanent central bank.
 * Safe to call twice: already-promoted rows and duplicates are linked, not re-created.
 */
class QuestionPromotionService
{
    /** Promote a standalone temp question. */
    public function promoteQuestion(TempQuestion $temp, ?int $adminId = null): Question
    {
        return DB::transaction(function () use ($temp, $adminId) {
            $temp = TempQuestion::withoutGlobalScopes()->lockForUpdate()->findOrFail($temp->id);

            if ($temp->group_id) {
                throw new DomainException('This question belongs to a group. Promote the group instead.');
            }

            // Already promoted and the bank row still exists.
            if ($temp->promoted_question_id && ($existing = Question::find($temp->promoted_question_id))) {
                return $existing;
            }

            // Duplicate already in the bank: link, don't copy.
            $question = Question::whereNull('group_id')
                ->where('content_hash', $temp->computeContentHash())
                ->first()
                ?? Question::create($this->questionAttributes($temp, $adminId));

            $temp->update(['promoted_question_id' => $question->id]);

            return $question;
        });
    }

    /** Promote a temp group and all its child questions. */
    public function promoteGroup(TempQuestionGroup $tempGroup, ?int $adminId = null): QuestionGroup
    {
        return DB::transaction(function () use ($tempGroup, $adminId) {
            $tempGroup = TempQuestionGroup::withoutGlobalScopes()->lockForUpdate()->findOrFail($tempGroup->id);

            if ($tempGroup->promoted_group_id && ($existing = QuestionGroup::find($tempGroup->promoted_group_id))) {
                return $existing;
            }

            $children = $tempGroup->questions()->orderBy('position')->get();
            $group = QuestionGroup::where('content_hash', QuestionGroup::hashFor($tempGroup->type, $tempGroup->content))->first();

            if ($group) {
                // Same passage already in the bank: link children by position.
                $banked = $group->questions()->get()->keyBy('position');

                foreach ($children as $child) {
                    if ($match = $banked->get($child->position)) {
                        $child->update(['promoted_question_id' => $match->id]);
                    }
                }
            } else {
                $group = QuestionGroup::create([
                    'created_by'   => $adminId,
                    'vetted'       => true,
                    'title'        => $tempGroup->title,
                    'introduction' => $tempGroup->introduction,
                    'content'      => $tempGroup->content,
                    'type'         => $tempGroup->type,
                ]);

                // Parent first, then children carrying the new group_id.
                foreach ($children as $child) {
                    $question = Question::create(
                        $this->questionAttributes($child, $adminId) + ['group_id' => $group->id]
                    );
                    $child->update(['promoted_question_id' => $question->id]);
                }
            }

            $tempGroup->update(['promoted_group_id' => $group->id]);

            return $group;
        });
    }

    /** Promote everything promotable in a CBT: groups first, then standalone questions. */
    public function promoteCbt(int $cbtId, ?int $adminId = null): array
    {
        $result = ['groups' => 0, 'questions' => 0, 'skipped' => []];

        $groups = TempQuestionGroup::withoutGlobalScopes()->where('cbt_id', $cbtId)->whereNull('promoted_group_id')->get();
        $questions = TempQuestion::withoutGlobalScopes()->where('cbt_id', $cbtId)->whereNull('group_id')->whereNull('promoted_question_id')->get();

        foreach ($groups as $g) {
            try {
                $this->promoteGroup($g, $adminId);
                $result['groups']++;
            } catch (ValidationException $e) {
                $result['skipped'][] = "group #{$g->id}: {$e->validator->errors()->first()}";
            }
        }

        foreach ($questions as $q) {
            try {
                $this->promoteQuestion($q, $adminId);
                $result['questions']++;
            } catch (ValidationException $e) {
                $result['skipped'][] = "question #{$q->id}: {$e->validator->errors()->first()}";
            }
        }

        return $result;
    }

    private function questionAttributes(TempQuestion $t, ?int $adminId): array
    {
        return [
            'created_by'       => $adminId,
            'vetted'           => true,
            'position'         => $t->position,
            'subject_id'       => $t->subject_id,
            'class_id'         => $t->class_id,
            'topic_id'         => $t->topic_id,
            'difficulty_level' => $t->difficulty_level,
            'text'             => $t->text,
            'type'             => $t->type,
            'marks'            => $t->marks,
            'options'          => $t->options,
            'correct_answers'  => $t->correct_answers,
        ];
    }
}
