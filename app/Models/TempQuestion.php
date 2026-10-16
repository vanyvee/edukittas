<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempQuestion extends Model
{

    protected $fillable = [
        'cbt_id',
        'source_question_id',
        'promoted_question_id',
        'group_id',
        'position',
        'question_number',
        'subject_id',
        'class_id',
        'topic_id',
        'difficulty_level',
        'text',
        'type',
        'marks',
        'options',
        'correct_answers',
];
    protected $casts = [
        'options' => 'array',            // null = subjective
        'correct_answers' => 'array',
    ];

    // Safety net: if a TempQuestion is ever returned as JSON by mistake, the answer key stays out.
    // Code can still read $question->correct_answers; only serialization is affected.
    protected $hidden = ['correct_answers'];

    public function cbt(): BelongsTo
    {
        return $this->belongsTo(Cbt::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TempQuestionGroup::class, 'group_id');
    }
}
