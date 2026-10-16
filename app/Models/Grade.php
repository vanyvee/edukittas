<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A student's marks for one subject, in one class, term and session.
 *
 * The table has no id/uuid — its real identity is the combination of
 * school_id, subject_id, class_id, student_id, term and session, which
 * is already its primary key at the database level. So this model is
 * read-only in the Eloquent sense: use it freely for querying and
 * relationships, but write through recordScore() below (or your own
 * DB::table('grades')->updateOrInsert(...) call), never ->save() or
 * ->find() — there is no single column for either to key off.
 */
class Grade extends Model
{
    protected $table = 'grades';

    /** No single-column key exists — block the methods that assume one. */
    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'subject_id',
        'class_id',
        'student_id',
        'sub_type',
        'first_test',
        'second_test',
        'exam',
        'compiled',
        'term',
        'session',
    ];

    protected $casts = [
        'first_test'  => 'integer',
        'second_test' => 'integer',
        'exam'        => 'integer',
        'compiled'    => 'boolean',
        'session'     => 'integer',
    ];

    // Relationships

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function myClass(): BelongsTo
    {
        return $this->belongsTo(MyClass::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The one place a score gets written, so every caller — controller,
     * import job, console command — goes through the same composite key.
     */
      public static function recordScore(array $identity, array $scores, bool $force = false): void
    {
        $key = [
            'school_id'  => $identity['school_id'],
            'subject_id' => $identity['subject_id'],
            'class_id'   => $identity['class_id'],
            'student_id' => $identity['student_id'],
            'term'       => $identity['term'],
            'session'    => $identity['session'],
        ];
 
        $existing = DB::table('grades')->where($key)->first();
 
        if ($existing && $existing->compiled && ! $force) {
            throw new \RuntimeException(
                'This grade has already been compiled and is locked. Unlock it before editing.'
            );
        }
 
        DB::table('grades')->updateOrInsert($key, $scores);
    }

}
