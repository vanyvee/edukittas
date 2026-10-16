<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Cbt extends Model
{
    public const TYPES = ['exam', 'test', 'practice', 'quiz'];

    // The host is set with $cbt->host()->associate($enrollmentOrUser), never mass-assigned.
    protected $fillable = [
        'school_id', 'title', 'type', 'duration_minutes', 'opens_at', 'closes_at', 'expires_at',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function host(): MorphTo
    {
        return $this->morphTo();
    }

    public function tempQuestions(): HasMany
    {
        return $this->hasMany(TempQuestion::class);
    }

    public function tempGroups(): HasMany
    {
        return $this->hasMany(TempQuestionGroup::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(CbtAttempt::class);
    }

    public function isOpen(): bool
    {
        return ! ($this->expires_at?->isPast()
            || $this->opens_at?->isFuture()
            || $this->closes_at?->isPast());
    }

    /** Same school for now; add class or assignment rules here later. */
    public function canBeTakenBy(Model $taker): bool
    {
        return (int) $taker->school_id === (int) $this->school_id;
    }
}
