<?php
 
namespace App\Support;
 
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
 
    public static function getSchoolId(): int
    {
        static::checkSchoolIdInSession();
 
        return (int) session('school_id');
    }
}
 