<?php

namespace App\Models;


use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Support\Helper;
class Student extends Authenticatable
{
   
    use HasUuids;
    use Notifiable;


protected static function booted(): void
{
    static::addGlobalScope('school', function (Builder $query) {
       Helper::checkSchoolIdInSession();
        $query->where('school_id', session('school_id')); // automatic, every query
    });

    static::addGlobalScope('active', function (Builder $query) {
        $query->where('status', '1');
    });

    static::creating(function (Student $student) {
        Helper::checkSchoolIdInSession();
        $student->school_id = session('school_id');
    });
}

public function scopeStatus(Builder $query, string $status): Builder
{
    $query->withoutGlobalScope('active'); // only lifts the status default — school stays locked

    return match ($status) {
        'current'       => $query->whereIn('status', ['0', '1']),
        'graduated' => $query->where('status', '2'),
        'active'    => $query->where('status', '1'),
        'inactive'  => $query->where('status', '0'),
         default     => throw new \InvalidArgumentException("Unknown student status filter: {$status}"),
    
    };
}
    protected $fillable = [
        'surname',
        'first_name',
        'other_name',
        'username',
        'dob',
        'sex',
        'class_id',
        'status',
        'addmission_date',
        'graduation_date',
        'sub_type',
        'pix',
        'post',
        'passport',
        'lg_id',
        'nin',
        'password',
        'phone',
        'email',
        'arm',
        'school_id',
        'guardian_id',
        'Fees',
    ];
  

    protected $hidden = ['password', 'nin'];

    protected $casts = [
        'dob'             => 'date',
        'addmission_date' => 'date',
        'graduation_date' => 'date',
       // 'status'          => StudentStatus::class,
        'password'        => 'hashed',
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

    public function myClass(): BelongsTo
    {
        return $this->belongsTo(MyClass::class, 'class_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lg_id');
    }

    

    
}