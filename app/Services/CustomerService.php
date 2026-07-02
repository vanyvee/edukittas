<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    // ─────────────────────────────────────────
    // LOOKUP BY TOKEN — called before checkout to check "do we know this device"
    // ─────────────────────────────────────────

    public function findByUuid(?string $uuid): ?Customer
    {
        if (!$uuid) return null;
        return Customer::where('uuid', $uuid)->first();
    }


    // ─────────────────────────────────────────
    // SCENARIO A — Token intact, customer confirms "still my number"
    // ─────────────────────────────────────────

    public function confirmReturning(string $uuid): ?Customer
    {
        $customer = Customer::where('uuid', $uuid)->first();
        if (!$customer) return null;

        // Nothing changes — just return them, order proceeds with existing details
        return $customer;
    }


    // ─────────────────────────────────────────
    // SCENARIO A (cont) — "No, not my number" — update existing row, same token
    // ─────────────────────────────────────────

    public function updateReturning(string $uuid, array $data): ?Customer
    {
        $customer = Customer::where('uuid', $uuid)->first();
        if (!$customer) return null;

        $customer->update([
            'first_name' => $data['first_name'] ?? $customer->first_name,
            'surname'    => $data['surname']    ?? $customer->surname,
            'phone'      => $data['phone']      ?? $customer->phone,
            'address'    => $data['address']    ?? $customer->address,
        ]);

        return $customer;
    }


    // ─────────────────────────────────────────
    // SCENARIO B — Token cleared, fresh details given, check by phone
    // ─────────────────────────────────────────

    public function findOrCreateByPhone(array $data): Customer
    {
        return DB::transaction(function () use ($data) {

            // Try to find an existing customer by phone — re-link them
            $customer = Customer::where('phone', $data['phone'])->first();

            if ($customer) {
                // Found them — their old uuid is still valid, just refresh details
                $customer->update([
                    'first_name' => $data['first_name'] ?? $customer->first_name,
                    'surname'    => $data['surname']    ?? $customer->surname,
                    'address'    => $data['address']    ?? $customer->address,
                ]);

                return $customer; // frontend will re-save customer->uuid to localStorage
            }

            // Truly new — create fresh row with new uuid
            return Customer::create([
              
                'first_name'    => $data['first_name'],
                'surname'       => $data['surname'],
                'phone'         => $data['phone'],
                'address'       => $data['address'],
               
            ]);
        });
    }


    // ─────────────────────────────────────────
    // OFFICIAL REGISTRATION — adds password, activates account
    // ─────────────────────────────────────────

    public function register(array $data, ?string $existingUuid = null): Customer
{
    return DB::transaction(function () use ($data, $existingUuid) {

        $customer = null;

        if ($existingUuid) {
            $customer = Customer::where('uuid', $existingUuid)->first();
        }

        if ($customer) {
            // Known guest — ONLY add username + password, never touch identity fields
            $customer->update([
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'status'   => 1,
            ]);

            return $customer;
        }

        // Brand new — full registration required
        return Customer::create([
           
            'first_name' => $data['first_name'],
            'surname'    => $data['surname'],
            'username'   => $data['username'],
            'phone'      => $data['phone'],
            'sex'      => $data['sex'],
            'password'   => Hash::make($data['password']),
            'address'    => $data['address'] ?? null,
            'status'     => 1,
        ]);
    });
}
}