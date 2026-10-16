<?php

namespace App\Services;

use App\Models\AccessRequest;
use App\Models\Enrollment;
use App\Models\Remark;
use App\Models\Student;
use App\Support\Helper;

/**
 * Both monetized lookups (trace + remarks) go through this one table/
 * service, distinguished by `type` — pending until paid, fulfilled (data
 * snapshotted into `payload`) only after payment. Nothing here reveals
 * data before `pay()` has run.
 */
class AccessRequestService extends Service
{
    public function requestTrace(Student $student, int $feeAmount): AccessRequest
    {
        return AccessRequest::create([
            'requesting_school_id' => Helper::schoolId(),
            'student_id'           => $student->id,
            'type'                 => 'trace',
            'fee_amount'           => $feeAmount,
        ]);
    }

    public function requestRemarks(Student $student, int $feeAmount): AccessRequest
    {
        return AccessRequest::create([
            'requesting_school_id' => Helper::schoolId(),
            'student_id'           => $student->id,
            'type'                 => 'remarks',
            'fee_amount'           => $feeAmount,
        ]);
    }

    /**
     * Marks paid and reveals the data in the same step — swap this for a
     * real payment-gateway webhook handler when one exists; nothing else
     * in this class needs to change.
     */
    public function pay(AccessRequest $request): AccessRequest
    {
        return $this->transaction(function () use ($request) {
            $request->update(['status' => 'paid', 'paid_at' => now()]);

            $payload = match ($request->type) {
                'trace'   => $this->buildTracePayload($request->student),
                'remarks' => $this->buildRemarksPayload($request->student),
            };

            $request->update(['status' => 'fulfilled', 'payload' => $payload]);

            return $request->fresh();
        });
    }

    protected function buildTracePayload(Student $student): array
    {
        $current = Enrollment::withoutGlobalScope('school')
            ->status('active')
            ->where('student_id', $student->id)
            ->with('school')
            ->first();

        return $current
            ? ['school_id' => $current->school_id, 'school_name' => $current->school->name]
            : ['school_id' => null, 'school_name' => null];
    }

    protected function buildRemarksPayload(Student $student): array
    {
        return Remark::where('student_id', $student->id)->get(['school_id', 'remark', 'created_at'])->toArray();
    }

    public function addRemark(Student $student, string $remark): Remark
    {
        return Remark::create([
            'student_id' => $student->id,
            'school_id'  => Helper::schoolId(),
            'remark'     => $remark,
        ]);
    }
}