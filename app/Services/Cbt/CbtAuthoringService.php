<?php

namespace App\Services\Cbt;

use App\Models\Cbt;
use App\Models\TempQuestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The authoring side of a CBT: create it, replace its questions, read them back for editing.
 * (The taking side lives in CbtService.)
 *
 * Expects blocks that have already been through QuestionValidator::validate().
 */
class CbtAuthoringService
{
    /**
     * @param  Model  $host   an Enrollment (student) or a User (teacher/staff)
     * @param  array  $meta   title, type, duration_minutes, opens_at, closes_at, expires_at
     * @param  array  $blocks cleaned blocks from QuestionValidator
     */
    public function create(Model $host, array $meta, array $blocks): Cbt
    {
        return DB::transaction(function () use ($host, $meta, $blocks) {
            $cbt = new Cbt($meta + ['school_id' => $host->school_id]);
            $cbt->host()->associate($host);
            $cbt->save();

            $this->replaceBlocks($cbt, $blocks);

            return $cbt;
        });
    }

    /** @param array|null $blocks null = leave the questions alone */
    public function update(Cbt $cbt, array $meta, ?array $blocks): Cbt
    {
        return DB::transaction(function () use ($cbt, $meta, $blocks) {
            if ($meta) {
                $cbt->fill($meta)->save();
            }

            if ($blocks !== null) {
                $this->assertQuestionsEditable($cbt);
                $this->replaceBlocks($cbt, $blocks);
            }

            return $cbt;
        });
    }

    /** True once anyone has started an attempt: the questions are then frozen. */
    public function isLocked(Cbt $cbt): bool
    {
        return $cbt->attempts()->exists();
    }

    private function assertQuestionsEditable(Cbt $cbt): void
    {
        // Replacing questions deletes the rows that attempt answers point at (cascade),
        // so it must never happen after a student has started.
        if ($this->isLocked($cbt)) {
            throw ValidationException::withMessages([
                'blocks' => 'Questions cannot be changed after someone has started this CBT.',
            ]);
        }
    }

    private function replaceBlocks(Cbt $cbt, array $blocks): void
    {
        $cbt->tempQuestions()->delete();
        $cbt->tempGroups()->delete();

        $number = 0; // question_number runs across the whole CBT, groups included

        foreach ($blocks as $block) {
            if ($block['kind'] === 'group') {
                $group = $cbt->tempGroups()->create($block['group']);

                foreach ($block['questions'] as $q) {
                    $this->storeQuestion($cbt, $q, $group->id, ++$number);
                }

                continue;
            }

            $this->storeQuestion($cbt, $block['question'], null, ++$number);
        }
    }

    private function storeQuestion(Cbt $cbt, array $q, ?int $groupId, int $number): void
    {
        $cbt->tempQuestions()->create([
            'group_id' => $groupId,
            'position' => $q['position'],
            'question_number' => $number,
            'subject_id' => $q['subject_id'],
            'class_id' => $q['class_id'],
            'topic_id' => $q['topic_id'],
            'difficulty_level' => $q['difficulty_level'],
            'text' => $q['text'],
            'type' => $q['type'],
            'marks' => $q['marks'],
            'options' => $q['options'],
            'correct_answers' => $q['correct_answers'],
            'content_hash' => $q['content_hash'],
        ]);
    }

    // ------------------------------------------------------------------
    // Read back for the editing panel (answer key included: author view only)
    // ------------------------------------------------------------------

    public function payload(Cbt $cbt): array
    {
        return [
            'cbt' => [
                'id' => $cbt->id,
                'title' => $cbt->title,
                'type' => $cbt->type,
                'duration_minutes' => $cbt->duration_minutes,
                'opens_at' => $cbt->opens_at?->toIso8601String(),
                'closes_at' => $cbt->closes_at?->toIso8601String(),
                'expires_at' => $cbt->expires_at?->toIso8601String(),
            ],
            'locked' => $this->isLocked($cbt),
            'blocks' => $this->blocksFor($cbt),
        ];
    }

    /** Same shape QuestionValidator accepts, so the panel can edit and resubmit as is. */
    public function blocksFor(Cbt $cbt): array
    {
        $questions = $cbt->tempQuestions()
            ->orderByRaw('question_number is null')
            ->orderBy('question_number')
            ->orderBy('id')
            ->get();
        $groups = $cbt->tempGroups()->get()->keyBy('id');

        $blocks = [];
        $seen = [];

        foreach ($questions as $q) {
            if ($q->group_id === null) {
                $blocks[] = ['kind' => 'question', 'question' => $this->authorQuestion($q)];
                continue;
            }
            if (isset($seen[$q->group_id])) {
                continue;
            }
            $seen[$q->group_id] = true;
            $group = $groups[$q->group_id];

            $blocks[] = [
                'kind' => 'group',
                'group' => [
                    'id' => $group->id,
                    'type' => $group->type,
                    'title' => $group->title,
                    'introduction' => $group->introduction,
                    'content' => $group->content,
                ],
                'questions' => $questions->where('group_id', $q->group_id)
                    ->sortBy('position')
                    ->map(fn (TempQuestion $c) => $this->authorQuestion($c))
                    ->values()
                    ->all(),
            ];
        }

        return $blocks;
    }

    private function authorQuestion(TempQuestion $q): array
    {
        return [
            'id' => $q->id,
            'position' => $q->position,
            'text' => $q->text,
            'options' => $q->options,
            'correct_answers' => $q->correct_answers, // hidden in toJson(), so read explicitly here
            'marks' => $q->marks,
            'subject_id' => $q->subject_id,
            'class_id' => $q->class_id,
            'topic_id' => $q->topic_id,
            'difficulty_level' => $q->difficulty_level,
        ];
    }
}
