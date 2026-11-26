<?php
 
namespace App\Supports;
 
use App\Events\RecordsUpserted;
use InvalidArgumentException;
/**
 * A plain, dependency-free helper for the "is there an active school in
 * session" check. Deliberately NOT a Service subclass — both the model
 * layer (User's global scope) and the service layer need this same guard,
 * and putting it on Service would make the model depend on the service
 * layer, which is backwards. This sits below both, so either can call it
 * without creating a circular dependency.
 */
class Helper
{
    public static function checkSchoolIdInSession(): void
    {
        abort_if(is_null(session('school_id')), 500, 'No active school in session.');
    }

    public static function schoolId(): int
    {
        return session('school_id') ?? throw new \RuntimeException('No active school in session.');
    }

    public static function setWardId(int $id): void
{
    session(['ward_id' => $id]);
}
 public static function setSchoolId(int $id): void
{
    session(['school_id' => $id]);
}
public static function currentWardId(): ?int
{
    $id = session('ward_id');

    return $id === null ? null : (int) $id;
}
public static function currentSchoolId(): ?int
{
    $id = session('school_id');

    return $id === null ? null : (int) $id;
}

/** Forgets the active school and ward, so a new login starts clean. */
public static function clearContext(): void
{
    session()->forget(['school_id', 'ward_id']);
}



    public static function sameSet(array $a, array $b): bool
{
    $norm = function (array $items): array {
        $items = array_values(array_unique(array_map(fn ($v) => trim((string) $v), $items)));
        sort($items, SORT_STRING);
        return $items;
    };
    return $norm($a) === $norm($b);
}

public static function normalizeText(string $text): string
{
    $text = mb_strtolower($text);
    $text = preg_replace('/^(?:(?!-\d)[\p{P}\p{S}\s])+/u', '', $text) ?? $text;
    $text = preg_replace('/[\p{P}\p{S}\s]+$/u', '', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

public static function hashOf(mixed $value): string
{
    return hash('sha256', json_encode($value, JSON_UNESCAPED_UNICODE));
}




    /**
     * WHAT IT DOES: Inserts new rows and updates existing ones in one query (Laravel upsert). One row or many.
     *    $uniqueBy must be backed by a unique or primary index. $strictOne = exactly one row or it throws.
     *    $notify fires App\\Events\\RecordsUpserted (upsert fires no model events). $update = columns to overwrite
     *    on a match (null = all). No casts, mutators, $fillable or scopes apply, so use it for plain data.
     *
     * GIVES BACK: Rows affected as the database counts them (not insert-vs-update).
     */
    public static function upsert(
        string $model,
        array $rows,
        array|string $uniqueBy,
        bool $notify = false,
        bool $strictOne = false,
        ?array $update = null,
    ): int {
        $rows = array_is_list($rows) ? $rows : [$rows];            // one row or many

        throw_if($strictOne && count($rows) !== 1, InvalidArgumentException::class, 'Expected exactly one record, got ' . count($rows) . '.');

        $affected = $model::upsert($rows, $uniqueBy, $update);

        if ($notify && $rows) {
            event(new RecordsUpserted($model, $rows, (array) $uniqueBy));
        }

        return $affected;
    }
 
}
 