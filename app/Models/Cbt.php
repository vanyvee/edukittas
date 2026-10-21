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
        'school_id', 'title', 'type', 'duration_minutes', 'opens_at', 'closes_at', 'expires_at', 'audience',
    ];

     protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'expires_at' => 'datetime',
        'notified_at' => 'datetime',
        'audience' => 'array',       // null = whole school, or {"classes":[ids], "enrollments":[ids]}
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

    /**
     * Is this person allowed to take this CBT?
     * Same school is always required. If the CBT has an audience, only the named classes and the
     * named students (enrollments) may take it; teachers and staff are not part of a targeted audience.
     */
    public function canBeTakenBy(Model $taker): bool
    {
        if ((int) $taker->school_id !== (int) $this->school_id) {
            return false;
        }
 
        $audience = $this->audience;
        if ($audience === null) {
            return true;                                   // open to the whole school
        }
 
        if ($taker->getMorphClass() !== (new Enrollment)->getMorphClass()) {
            return false;
        }
 
        return in_array((int) $taker->getKey(), $audience['enrollments'] ?? [], true)
            || in_array((int) $taker->class_id, $audience['classes'] ?? [], true);
    }
}
