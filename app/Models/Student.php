<?php

namespace App\Models;


use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Support\Helper;
use App\Traits\BelongsToThisSchool;


class Student extends Authenticatable
{
   
    use HasUuids;
    use Notifiable;
    use BelongsToThisSchool;

// protected static function booted(): void
// {
//     static::addGlobalScope('active', function (Builder $query) {
//         $query->where('status', '1');
//     });

//     static::creating(function (Student $student) {
//         Helper::checkSchoolIdInSession();
//         $student->school_id = session('school_id');
//     });
// }

// public function scopeStatus(Builder $query, string $status): Builder
// {
//     $query->withoutGlobalScope('active'); // only lifts the status default — school stays locked

//     return match ($status) {
//         'current'       => $query->whereIn('status', ['0', '1']),
//         'graduated' => $query->where('status', '2'),
//         'active'    => $query->where('status', '1'),
//         'inactive'  => $query->where('status', '0'),
//          default     => throw new \InvalidArgumentException("Unknown student status filter: {$status}"),
    
//     };
// }
   




    /** Generate the uuid column, keep id as the primary key. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    
     protected $fillable = [
        'uuid', 'surname', 'first_name', 'other_name', 'username',
        'dob', 'sex', 'pix', 'passport', 'lg_id', 'nin',
        'phone', 'email', 'guardian_id',
    ];
 
    protected $hidden = [
        'password',
    ];
 
    protected $casts = [
        'dob' => 'date',
        'password'        => 'hashed',
    ];
 
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
 
    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lg_id');
    }
 
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
 
    /**
     * Active enrollments only — a student can have more than one active
     * row only in an edge case (e.g. dual-registered), but never assumes
     * exactly one. Callers needing "the" current school still have to
     * pick which enrollment they mean (see Enrollment's own school scope).
     */
    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', '1');
    }

    
}