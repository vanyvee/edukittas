<?php

namespace App\Models;

use App\Models\Concerns\ValidatesMarkable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use ValidatesMarkable;

    protected $fillable = [
        'created_by', 'vetted', 'content_hash',
        'group_id', 'position',
        'subject_id', 'class_id', 'topic_id', 'difficulty_level',
        'text', 'type', 'marks', 'options', 'correct_answers',
    ];

    protected $casts = [
        'vetted'          => 'boolean',
        'options'         => 'array',   // null = subjective
        'correct_answers' => 'array',
    ];

    // Answer key never leaks through JSON. Code can still read it directly.
    protected $hidden = ['correct_answers'];

    protected static function booted(): void
    {
        // Runs after the trait's validation hook (trait boots first).
        static::saving(fn (Question $q) => $q->content_hash = $q->computeContentHash());
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(QuestionGroup::class, 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
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
