<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Expected, user-facing CBT failures. Laravel calls render() automatically
 * when one of these escapes a controller, so no try/catch is needed there.
 */
class CbtException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly string $reason = 'cbt_error',
    ) {
        parent::__construct($message);
    }

    public static function wrongMode(): self
    {
        return new self('This activity is a live quiz and cannot be taken as a timed test.', 422, 'wrong_mode');
    }

    public static function notAllowed(): self
    {
        return new self('You are not allowed to take this test.', 403, 'not_allowed');
    }

    public static function closed(): self
    {
        return new self('This test is not open.', 403, 'closed');
    }

    public static function alreadyTaken(): self
    {
        return new self('You have already taken this test.', 409, 'already_taken');
    }

    public static function alreadySubmitted(): self
    {
        return new self('This attempt has already been submitted.', 409, 'already_submitted');
    }

    public static function timeUp(): self
    {
        return new self('Time is up for this attempt.', 422, 'time_up');
    }

    public static function notSubmitted(): self
    {
        return new self('Results are available after the attempt is submitted.', 409, 'not_submitted');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage(), 'code' => $this->reason], $this->status);
    }
}
