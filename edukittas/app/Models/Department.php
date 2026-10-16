<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    protected $fillable = ['name'];

    public $timestamps = false;

    /** Staff in this department, across whichever schools they're in it for. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_users')
            ->withPivot('school_id')
            ->withTimestamps();
    }

    /**
     * Schools that have this department. distinct() because two users
     * in the same department at the same school would otherwise list
     * that school twice.
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'department_users')
            ->withPivot('user_id')
            ->withTimestamps()
            ->distinct();
    }
}