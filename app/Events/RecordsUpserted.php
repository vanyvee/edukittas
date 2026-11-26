<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired by Helper::upsert() when asked to notify. Eloquent's own created/updated/saved events do NOT
 * fire on an upsert, so this is the one signal listeners get. One listener can serve many models:
 * check $event->model. Waits for the surrounding transaction to commit before it fires.
 */
class RecordsUpserted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param class-string $model     the model class that was written
     * @param array        $rows      exactly what was sent to upsert (raw values, not model objects)
     * @param array        $uniqueBy  the columns that decided insert vs update
     */
    public function __construct(
        public readonly string $model,
        public readonly array $rows,
        public readonly array $uniqueBy,
    ) {
    }
}
