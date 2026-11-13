<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Helper;
//use App\Traits\BelongsToSchool;

class User extends Authenticatable
{
   
    use  Notifiable;


   
    protected $fillable = [
        'surname', 'first_name',  'other_name', 'username', 'role', 'status', 'salary', 'account_number', 'bank_name',
        'passport', 'sex', 'dob', 'lg_id', 'employed_on', 'form_teacher', 'qualification', 'phone', 'password', 'email',
        'recovery_phone', 'address',
    ];
 
    protected $hidden = [ 'password', 'salary', 'account_number', 'bank_name', ];

    protected $casts = [
        'dob'               => 'date',
        'employed_on'       => 'datetime',
        'email_verified_at' => 'datetime',
        'status'            => 'boolean',
        'password'          => 'hashed',
    ];

 


    /** Generate the uuid column, keep id as the primary key. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
 
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
 

 
   // Relationships
 
    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lg_id');
    }
 
    /**
     * Departments this user sits in. school_id rides along on the pivot
     * because departments (like classes) are shared labels, not owned
     * by one school.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_users')
            ->withPivot('school_id')
            ->withTimestamps();
    }
 

 /**
     * Schools this user belongs to, derived from department_users
     * (which already supports several schools per user — no separate
     * membership table needed for the multi-school director case).
     * distinct() guards against a user holding two departments in the
     * same school and getting that school back twice.
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'department_users', 'user_id', 'school_id')
            ->withPivot('department_id')
            ->withTimestamps()
            ->distinct();
    }

    public function cbts()
    {
        return $this->morphMany(Cbt::class, 'host');
    }

    /** Subject/class teaching assignments — see ClassSubjectTeacher. */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(ClassSubjectTeacher::class, 'user_id');
    }
 
    // Scopes
 
    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }
 
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }
    


   
}
