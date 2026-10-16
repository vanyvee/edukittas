<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Question extends Model
{
    protected $fillable = [
        'subject_id', 'exam_type', 'type', 'group_id',
        'text', 'passage', 'options', 'correct_answer',
    ];

    protected $casts = [
        'options'        => 'array',
        'correct_answer' => 'array',   // works for both "4" and ["A","D"]
    ];

    // Safety net: if anyone ever returns a Question through Eloquent/JSON, the key never leaks.
    protected $hidden = ['correct_answer'];

    protected static function booted(): void
    {
        // A gap/comprehension child inherits subject + exam from its parent,
        // so the seeder/admin only has to set them once on the parent.
        static::creating(function (Question $q) {
            if ($q->group_id && ($parent = static::find($q->group_id))) {
                $q->subject_id ??= $parent->subject_id;
                $q->exam_type  ??= $parent->exam_type;
            }
        });

        // Catch data-entry mistakes at write time (a typo in correct_answer = a question nobody can get right).
        static::saving(fn (Question $q) => $q->assertMarkable());
    }

    /** Group parents (passage set) have no options; everything else must be markable. */
    private function assertMarkable(): void
    {
        if ($this->passage !== null) {
            return;
        }

        $options = array_map('strval', (array) $this->options);
        $correct = array_map('strval', (array) $this->correct_answer);

        if (count($options) < 2) {
            throw new InvalidArgumentException('A question needs at least two options.');
        }
        if (count($options) !== count(array_unique($options))) {
            throw new InvalidArgumentException('Options must be unique (duplicates make marking ambiguous).');
        }
        if ($correct === []) {
            throw new InvalidArgumentException('correct_answer is required.');
        }
        foreach ($correct as $answer) {
            if (! in_array($answer, $options, true)) {
                throw new InvalidArgumentException("correct_answer \"{$answer}\" is not one of the options.");
            }
        }
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'group_id')->orderBy('id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'group_id');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('group_id');
    }
}
