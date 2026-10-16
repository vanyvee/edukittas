<?php

namespace App\Events;

use App\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;

class StudentEnrolled
{
    use Dispatchable;

    public function __construct(public Enrollment $enrollment)
    {
    }
}