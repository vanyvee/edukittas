<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Admission enquiries from a school's public page.
 */
class EnquiryService extends Service
{
    /**
     * WHAT IT DOES: Saves an enquiry for a school. The same phone number asking the same school twice
     *    within a day is saved once, so pressing Send twice does not create duplicates.
     *
     * USED WHEN: POST /public/schools/{id}/enquiries.
     *
     * GIVES BACK: Nothing.
     */
    public function submit(int $schoolId, array $data, ?string $ip): void
    {
        $already = DB::table('school_enquiries')
            ->where('school_id', $schoolId)
            ->where('phone', $data['phone'])
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($already) {
            return;
        }

        DB::table('school_enquiries')->insert([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'message' => $data['message'],
            'status' => 'new',
            'ip_address' => $ip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
