<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the claim-link a guardian receives after being auto-created
 * during student registration (no password set at that point — see
 * StudentService::sendGuardianClaimLink()). The signed URL itself is the
 * proof of ownership; nothing extra is stored to validate it.
 */
class GuardianClaimController extends Controller
{
    public function show(Request $request, Guardian $guardian)
    {
        abort_unless($request->hasValidSignature(), 403, 'This link is invalid or has expired.');

        return view('guardian.claim', ['guardian' => $guardian]);
    }

    public function store(Request $request, Guardian $guardian)
    {
        abort_unless($request->hasValidSignature(), 403, 'This link is invalid or has expired.');

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $guardian->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('login')->with('status', 'Your account is ready — you can now log in.');
    }
}