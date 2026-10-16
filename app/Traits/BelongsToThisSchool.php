<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Supports\Helper;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool()
    {
        static::addGlobalScope('school', function (Builder $query) {

            Helper::checkSchoolIdInSession();

            $query->where('school_id', session('school_id'));
        });
    }
}

