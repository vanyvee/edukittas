<?php

namespace App\Services;

use App\Models\User;

class TeacherService extends UserService
{

    public function findTeacher(int $id)
    {
        $user = $this->findById($id);

        if (!$user || $user->role !== 'teacher') {
            return null;
        }

        return $user;
    }

    public function assignClass(int $teacherId, int $classId)
    {
        $teacher = $this->findTeacher($teacherId);

        if (!$teacher) {
            throw new \Exception('Teacher not found.');
        }

        // Teacher-specific logic here

        return $teacher;
    }
}