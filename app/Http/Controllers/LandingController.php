<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function home()
    {
         $schools = [];

    $landing = null;
        // $schools = DB::table('schools')
        //     ->where('featured', true)
        //     ->get();

        return view('app', compact('schools', 'landing'));
    }

    public function codedEntry(string $code)
    {
        // Affiliate: exactly 6 digits
        if (preg_match('/^\d{6}$/D', $code)) {

            $affiliate = DB::table('affiliates')
                ->where('url_code', $code)
                ->first();

            abort_if(!$affiliate, 404);

            return view('app', compact('affiliate'));
        }

        // School: 1–19 characters, letters and underscores only.
        // Must contain at least one letter.
        if (
            preg_match('/^(?=.*[a-zA-Z])[a-zA-Z_]{1,19}$/D', $code)
        ) {

            $school = DB::table('schools')
                ->where('url_code', $code)
                ->first();

            abort_if(!$school, 404);

            return view('app', compact('school'));
        }

        abort(404);
    }
}