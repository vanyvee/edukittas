<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Remark extends Model
{
    protected $fillable = ['student_id', 'school_id', 'remark'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}