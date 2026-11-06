<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'school_id', 'created_by', 'vetted', 'school_hidden', 'content_hash',
        'group_id', 'position',
        'subject_id', 'class_id', 'topic_id', 'difficulty_level',
        'text', 'type', 'marks', 'options', 'correct_answers',
    ];

    protected $casts = [
        'vetted'          => 'boolean',
        'school_hidden'   => 'boolean',
        'options'         => 'array',   // null = subjective
        'correct_answers' => 'array',
    ];

    // Answer key stays out of JSON. Code can still read $question->correct_answers.
    protected $hidden = ['correct_answers'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(QuestionGroup::class, 'group_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeStandalone(Builder $query): Builder
    {
        return $query->whereNull('group_id');
    }

    public function scopeVetted(Builder $query): Builder
    {
        return $query->where('vetted', true);
    }
}