<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TempQuestionGroup extends Model
{
   protected $fillable = [
    'cbt_id',
    'source_group_id',
    'promoted_group_id',
    'title',
    'introduction',
    'content',
    'type',
];
    public function cbt(): BelongsTo
    {
        return $this->belongsTo(Cbt::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TempQuestion::class, 'group_id');
    }
}
