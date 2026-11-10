<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Notifications\Notifiable;

class Guardian extends Model
{
    use HasUuids;
    use Notifiable;

    protected $fillable = [
        'surname',
        'other_name',
        'username',
        'sex',
        'passport',
        'password',
        'phone',
        'email',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
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

    /** Wards — the guardian's own students. */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'guardian_id');
    }



    /**
     * Schools this guardian is connected to. There's no guardian_school
     * pivot — a guardian belongs to a school only by virtue of having a
     * ward enrolled there, so this walks through students instead.
     * distinct() collapses schools with more than one ward in them.
     */
    public function schools(): HasManyThrough
    {
        return $this->hasManyThrough(
            School::class,
            Student::class,
            'guardian_id', // students.guardian_id
            'id',          // schools.id
            'id',          // guardians.id
            'school_id'    // students.school_id
        )->distinct();
    }
}
