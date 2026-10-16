<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MyClass extends Model
{
    protected $table = 'my_classes';

    protected $fillable = ['name', 'level'];

    public $timestamps = false;

    // Relationships

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(ClassSubjectTeacher::class, 'class_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'class_id');
    }
}