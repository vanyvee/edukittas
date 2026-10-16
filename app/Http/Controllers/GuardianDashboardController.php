<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * No school context here on purpose — a guardian isn't scoped to one
 * school the way staff are (see the earlier design discussion). This is
 * the single query that resolves "all my kids, wherever they are."
 */
class GuardianDashboardController extends Controller
{
    public function index()
    {
        $guardian = Auth::guard('guardian')->user();

        $students = $guardian->students()
            ->with(['enrollments' => fn ($q) => $q->withoutGlobalScopes()->with('school', 'myClass')])
            ->get();

        return response()->json(['students' => $students]);
    }
}