<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRequest extends Model
{
    protected $fillable = ['requesting_school_id', 'student_id', 'type', 'status', 'fee_amount', 'paid_at', 'payload'];

    protected $casts = [
        'payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}