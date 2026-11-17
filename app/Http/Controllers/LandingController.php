<?php

namespace App\Http\Controllers;


use App\Services\PublicSchoolService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
/**
 * The short links. Every answer hands the page a `landing` value (window.__LANDING__) saying what
 * kind of link was opened:
 *   null                           the Edukittas home page
 *   { type: 'school', school }     a school's own link, shown as that school's site with no Edukittas branding
 *   { type: 'affiliate', code }    an affiliate's link, shown as the Edukittas home page
 */
class LandingController extends Controller
{
    /**
     * WHAT IT DOES: Opens the Edukittas home page.
     *
     * USED WHEN: GET / on the main domain.
     *
     * GIVES BACK: The app page.
     */
    public function home()
    {
        $schools = [];
        $landing = null;

        return view('app', compact('schools', 'landing'));
    }



     /**
     * WHAT IT DOES: Opens a short link. Six digits is an affiliate code; letters and underscores are a
     *    school's code. Anything else, or a code nobody owns, is a 404. A school's data goes with the
     *    page, so the school's site shows at once with no second request.
     *
     * USED WHEN: GET /{code} on the short-link domain.
     *
     * GIVES BACK: The app page, or a 404.
     */
    public function codedEntry(string $code, PublicSchoolService $schools)
    {
        if (preg_match('/^\d{6}$/D', $code)) {
            // The affiliates table arrives with the affiliate accounts; until then these links are a 404.
            abort_unless(Schema::hasTable('affiliates'), 404);

            $affiliate = DB::table('affiliates')->where('url_code', $code)->first(['id']);
            abort_if(! $affiliate, 404);

            $landing = ['type' => 'affiliate', 'code' => $code];

            return view('app', compact('landing'));
        }

        if (preg_match('/^(?=.*[a-zA-Z])[a-zA-Z_]{1,19}$/D', $code)) {
            $school = $schools->byCode($code);
            abort_if($school === null, 404);

            $landing = ['type' => 'school', 'school' => $school];

            return view('app', compact('landing'));
        }

        abort(404);
    }


}