<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbtAttemptAnswer extends Model
{
    // Rows are written with upsert() by CbtService, so no $fillable is needed.

    protected $casts = [
        'answer' => 'json',          // string | string[] | null
        'is_correct' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(CbtAttempt::class, 'cbt_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TempQuestion::class, 'temp_question_id');
    }
}
