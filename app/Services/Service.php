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





    /**
     * WHAT IT DOES: updateOrCreate in a loop, inside one transaction (all rows save or none). One row or many.
     *    Mainly for authenticatable models, so casts like the hashed password still run.
     *    $notify true = normal model events fire; false = saved quietly (all model events muted meanwhile).
     *    $strictOne = exactly one row or it throws. $matchBy columns must be in the model's $fillable.
     *
     * GIVES BACK: Collection of saved models; ->wasRecentlyCreated is true for inserts.
     */
    protected function updateOrCreateMany(
        string $model,
        array $rows,
        array|string $matchBy,
        bool $notify = false,
        bool $strictOne = false,
    ): Collection {
        $rows = array_is_list($rows) ? $rows : [$rows];            // one row or many
        $keys = (array) $matchBy;

        throw_if($strictOne && count($rows) !== 1, InvalidArgumentException::class, 'Expected exactly one record, got ' . count($rows) . '.');

        // A row with no match value would run updateOrCreate with an empty WHERE and overwrite the FIRST record in the table.
        throw_unless(
            collect($rows)->every(fn ($row) => collect($keys)->every(fn ($k) => filled($row[$k] ?? null))),
            InvalidArgumentException::class,
            'Every row needs a value for: ' . implode(', ', $keys)
        );

        $save = fn () => $this->transaction(
            fn () => collect($rows)->map(fn ($row) => $model::updateOrCreate(Arr::only($row, $keys), Arr::except($row, $keys)))
        );

        return $notify ? $save() : $model::withoutEvents($save);
    }
}