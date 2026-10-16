<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

abstract class Service
{
    /** Run a block of writes atomically — all or nothing. */
    protected function transaction(\Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}