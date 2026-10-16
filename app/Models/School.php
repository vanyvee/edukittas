<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name', 'lg_id', 'dob', 'phone', 'email', 'address', 'pix', 'status', 'current_session',
        'current_term', 'type', 'promotion_grade', 'logo', 'result_template', 'theme',
    ];

    protected $casts = [
        'dob'             => 'date',
        'status'          => 'boolean',
        'type'            => 'boolean',
        'promotion_grade' => 'integer',
    ];

    // Relationships

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lg_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /** Staff attached to this school through department_users. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_users')
            ->withPivot('department_id')
            ->withTimestamps()
            ->distinct();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_users')
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(ClassSubjectTeacher::class);
    }
}