<?php

namespace App\Http\Controllers;

use App\Services\CustomerService;
use App\Http\Requests\CustomerLookupRequest;
use App\Http\Requests\ConfirmDetailsRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $service) {}


    // ─────────────────────────────────────────
    // LOOKUP — "do we know this token"
    // ─────────────────────────────────────────

    public function lookup(CustomerLookupRequest $request)
    {
        $customer = $this->service->findByUuid($request->validated('uuid'));

        if (!$customer) {
            return response()->json(['known' => false]);
        }

        return response()->json([
            'known'   => true,
            'name'    => trim($customer->first_name . ' ' . $customer->surname),
            'phone'   => $customer->phone,
            'address' => $customer->address,
        ]);
    }


    // ─────────────────────────────────────────
    // CONFIRM DETAILS — yes/no + scenario B fallback
    // ─────────────────────────────────────────

    public function confirmDetails(ConfirmDetailsRequest $request)
    {
        $data = $request->validated();

        // Scenario A — confirmed yes
        if ($data['confirmed'] && !empty($data['uuid'])) {
            $customer = $this->service->confirmReturning($data['uuid']);
            return response()->json(['customer' => $customer]);
        }

        // Scenario A — confirmed no, update existing token's row
        if (!$data['confirmed'] && !empty($data['uuid'])) {
            $customer = $this->service->updateReturning($data['uuid'], $data);
            if ($customer) {
                return response()->json(['customer' => $customer]);
            }
        }

        // Scenario B — no token at all, lookup/create by phone
        $customer = $this->service->findOrCreateByPhone($data);
        return response()->json(['customer' => $customer]);
    }


    // ─────────────────────────────────────────
    // OFFICIAL REGISTRATION
    // ─────────────────────────────────────────

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $existingUuid = $data['uuid'] ?? null;

        $customer = $this->service->register($data, $existingUuid);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return response()->json([
            'type' => 'customer',
            'user' => $customer,
        ]);
    }
}