<?php

// Save as app/Traits/BelongsToSchool.php (the file name must match the trait name).

namespace App\Traits;

use App\Support\Helper;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $query) {
            Helper::checkSchoolIdInSession();

            // Qualified with the table name, so a join with another school_id column is not ambiguous.
            $query->where($query->getModel()->qualifyColumn('school_id'), session('school_id'));
        });
    }
}