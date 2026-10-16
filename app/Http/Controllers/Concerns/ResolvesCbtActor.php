<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ResolvesCbtActor
{
    /** Students act through their current enrollment; teachers and staff act as themselves. */
    protected function cbtActor(Request $request): Model
    {
        $user = $request->user();

        $actor = $user instanceof Student ? $user->currentEnrollment() : $user;

        return $actor ?? abort(403, 'No active enrollment.');
    }
}
