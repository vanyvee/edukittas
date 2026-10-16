<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A teacher's assignment to one subject in one class, for one school.
 * Modelled directly (not as a plain belongsToMany pivot) because the
 * table carries its own identity (uuid) and real attributes (cls_fee,
 * sub_type) — Eloquent has no native support for the table's actual
 * composite primary key, so uuid stands in as the key instead.
 */
class ClassSubjectTeacher extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'class_id',
        'subject_id',
        'school_id',
        'cls_fee',
        'sub_type',
    ];

    protected $casts = [
        'cls_fee' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function myClass(): BelongsTo
    {
        return $this->belongsTo(MyClass::class, 'class_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}