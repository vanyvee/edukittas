<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Supports\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolController extends Controller
{
    /**
     * WHAT IT DOES: Sets the colour theme of the school the leader is working in.
     *
     * USED WHEN: A director, principal, vice principal or school admin picks a colour in Settings.
     *    Route is behind can:manage-school-theme; demo schools are read-only via demo.readonly.
     *
     * GIVES BACK: { theme: 'default' | 'theme1' | ... }
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'string', Rule::exists('themes', 'code')->where('is_active', true)],
        ]);

        $school = School::findOrFail(Helper::currentSchoolId());
        $school->update(['theme' => $data['theme']]);

        return response()->json(['theme' => $school->theme]);
    }
}