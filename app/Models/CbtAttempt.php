<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CbtAttempt extends Model
{
    protected $fillable = ['cbt_id', 'started_at', 'ends_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function cbt(): BelongsTo
    {
        return $this->belongsTo(Cbt::class);
    }

    /** An Enrollment (student) or a User (teacher/staff). */
    public function taker(): MorphTo
    {
        return $this->morphTo();
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CbtAttemptAnswer::class);
    }

    public function isOwnedBy(Model $taker): bool
    {
        return $this->taker_type === $taker->getMorphClass()
            && (int) $this->taker_id === (int) $taker->getKey();
    }
}
