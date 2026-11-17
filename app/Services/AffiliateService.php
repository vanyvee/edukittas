<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Affiliate applications from the guest page. An application is only a lead. Turning it into an
 * affiliate account (with its own link code) happens later, when an admin approves it.
 */
class AffiliateService extends Service
{
    /**
     * WHAT IT DOES: Saves an application. If the same phone number already has a pending
     *    application, nothing new is saved, so pressing Apply twice does not create duplicates.
     *
     * USED WHEN: POST /public/affiliates.
     *
     * GIVES BACK: Nothing.
     */
    public function apply(array $data, ?string $ip): void
    {
        $already = DB::table('affiliate_applications')
            ->where('phone', $data['phone'])
            ->where('status', 'pending')
            ->exists();

        if ($already) {
            return;
        }

        DB::table('affiliate_applications')->insert([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'pending',
            'ip_address' => $ip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
