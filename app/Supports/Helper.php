<?php
 
namespace App\Supports;
 
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
 
}
 