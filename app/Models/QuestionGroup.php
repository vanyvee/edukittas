<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionGroup extends Model
{
    protected $fillable = [
        'school_id', 'created_by', 'vetted', 'school_hidden',
        'title', 'introduction', 'content', 'type',
    ];

    protected $casts = [
        'vetted'        => 'boolean',
        'school_hidden' => 'boolean',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'group_id')->orderBy('position');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}