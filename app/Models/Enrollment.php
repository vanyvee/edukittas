<?php
 
namespace App\Models;
 
use App\Support\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
 
/**
 * One row per school a student has ever attended. This is where
 * school_id, class_id, arm, and status live now — they moved here from
 * Student, since all four change per-enrollment, not per-identity.
 * The 'school' and 'active' global scopes (and the auto-set of school_id
 * on creating()) are the direct equivalent of the pattern the original
 * Student model used, just relocated to where the columns actually are.
 */
class Enrollment extends Model
{
    protected $fillable = [
        'student_id', 'school_id', 'class_id', 'status',
        'admission_date', 'graduation_date', 'sub_type', 'arm',
        'post', 'fees',
    ];
 
    protected $casts = [
        'admission_date'   => 'date',
        'graduation_date'  => 'date',
    ];
 
    protected static function booted(): void
    {
        static::addGlobalScope('school', function (Builder $query) {
            Helper::checkSchoolIdInSession();
            $query->where('school_id', session('school_id'));
        });
 
        static::addGlobalScope('active', function (Builder $query) {
            $query->where('status', '1');
        });
 
        static::creating(function (Enrollment $enrollment) {
            Helper::checkSchoolIdInSession();
            $enrollment->school_id ??= session('school_id');
        });
    }
 
    /**
     * Same tri-state shape as before: current/graduated/active/inactive/all,
     * lifting only the 'active' global default — 'school' stays locked.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        $query->withoutGlobalScope('active');
 
        return match ($status) {
            'current'   => $query->whereIn('status', ['0', '1']),
            'graduated' => $query->where('status', '2'),
            'active'    => $query->where('status', '1'),
            'inactive'  => $query->where('status', '0'),
            'all'       => $query,
            default     => throw new \InvalidArgumentException("Unknown enrollment status filter: {$status}"),
        };
    }
 
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
 
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
 
    public function myClass(): BelongsTo
    {
        return $this->belongsTo(MyClass::class, 'class_id');
    }

    public function cbts()
    {
        return $this->morphMany(Cbt::class, 'host');
    }
}
 