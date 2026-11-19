<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * What a guest may see about a school. Everything here is public, so it never reads the session's
 * school and goes through DB::table() (no model scopes). Only schools that are active (status = 1)
 * are ever shown. Answers are remembered for a few minutes, because every visitor asks the same thing.
 */
class PublicSchoolService extends Service
{
    private const TTL = 300;             // seconds
    private const FEATURED_LIMIT = 12;
    private const COUNTRY_CODE = '234';  // for turning 0803... into a WhatsApp number

    /**
     * WHAT IT DOES: Lists the featured, active schools for the hero and the school picker.
     *
     * USED WHEN: GET /public/schools/featured, when the guest page opens.
     *
     * GIVES BACK: A list of {id, name, logo, cover, motto, location, url_code}.
     */
    public function featured(): array
    {
        return Cache::remember('public.schools.featured', self::TTL, function () {
            return DB::table('schools as s')
                ->join('lgas as l', 'l.id', '=', 's.lg_id')
                ->where('s.status', 1)
                ->where('s.featured', 1)
                ->orderBy('s.name')
                ->limit(self::FEATURED_LIMIT)
                ->get(['s.id', 's.name', 's.logo', 's.pix', 's.motto', 's.url_code', 'l.name as lga', 'l.state'])
                ->map(fn ($s) => [
                    'id' => (int) $s->id,
                    'name' => $s->name,
                    'logo' => $this->fileUrl($s->logo),
                    'cover' => $this->fileUrl($s->pix),
                    'motto' => $s->motto,
                    'location' => $this->location($s),
                    'url_code' => $s->url_code,
                ])
                ->all();
        });
    }

    /**
     * WHAT IT DOES: One school's public profile: headline facts, contacts (with a WhatsApp link), the
     *    about text, photos and the school's theme. Anything not filled in is left out.
     *
     * USED WHEN: GET /public/schools/{id}, and inside byCode.
     *
     * GIVES BACK: The profile array, or null when the school does not exist or is not active.
     */
    public function profile(int $id): ?array
    {
        return Cache::remember("public.school.$id", self::TTL, function () use ($id) {
            $s = DB::table('schools as s')
                ->join('lgas as l', 'l.id', '=', 's.lg_id')
                ->where('s.id', $id)
                ->where('s.status', 1)
                ->first(['s.id', 's.name', 's.logo', 's.pix', 's.motto', 's.about', 's.website', 's.photos',
                    's.phone', 's.email', 's.address', 's.dob', 's.current_session', 's.current_term',
                    's.theme', 's.url_code', 'l.name as lga', 'l.state']);

            if (! $s) {
                return null;
            }

            return [
                'id' => (int) $s->id,
                'name' => $s->name,
                'url_code' => $s->url_code,
                'theme' => $s->theme,
                'logo' => $this->fileUrl($s->logo),
                'cover' => $this->fileUrl($s->pix),
                'motto' => $s->motto,
                'location' => $this->location($s),
                'about' => $s->about,
                'facts' => $this->facts($s),
                'contacts' => array_filter([
                    'phone' => $s->phone,
                    'whatsapp' => $this->whatsapp($s->phone),
                    'email' => $s->email,
                    'address' => $s->address,
                    'website' => $s->website,
                ]),
                'photos' => array_values(array_filter(array_map(
                    fn ($p) => $this->fileUrl($p),
                    json_decode($s->photos ?? '[]', true) ?: [],
                ))),
            ];
        });
    }

    /**
     * WHAT IT DOES: Finds an active school by its short link code (the "greenfield" in
     *    edukit.test/greenfield) and returns its profile.
     *
     * USED WHEN: GET /public/schools/by-code/{code}, and when the landing page opens a school link.
     *
     * GIVES BACK: The profile array, or null.
     */
    public function byCode(string $code): ?array
    {
        $id = Cache::remember(
            'public.school.code.' . Str::lower($code),
            self::TTL,
            fn () => DB::table('schools')->where('url_code', $code)->where('status', 1)->value('id'),
        );

        return $id ? $this->profile((int) $id) : null;
    }

    /**
     * WHAT IT DOES: Builds the headline facts shown as small cards: founding year, current session
     *    and term, and head counts. Counts only, never names.
     *
     * USED WHEN: Inside profile.
     *
     * GIVES BACK: A list of {label, value}.
     */
    private function facts(object $s): array
    {
        $students = DB::table('enrollments')->where('school_id', $s->id)->where('status', '1')->count();

        $teachers = DB::table('department_users as du')
            ->join('users as u', 'u.id', '=', 'du.user_id')
            ->where('du.school_id', $s->id)
            ->where('u.role', 'teacher')
            ->distinct()
            ->count('u.id');

        return array_values(array_filter([
            $s->dob ? ['label' => 'Founded', 'value' => substr((string) $s->dob, 0, 4)] : null,
            $s->current_session ? ['label' => 'Session', 'value' => $s->current_session . ', term ' . $s->current_term] : null,
            $students > 0 ? ['label' => 'Students', 'value' => (string) $students] : null,
            $teachers > 0 ? ['label' => 'Teachers', 'value' => (string) $teachers] : null,
        ]));
    }

    /**
     * WHAT IT DOES: Joins the LGA and state into one line, for example "Obio/Akpor, Rivers".
     *
     * USED WHEN: Inside featured and profile.
     *
     * GIVES BACK: A string.
     */
    private function location(object $s): string
    {
        return trim(($s->lga ?? '') . ', ' . ($s->state ?? ''), ', ');
    }

    /**
     * WHAT IT DOES: Turns a school phone number into a WhatsApp chat link. A number starting with 0 is
     *    given the country code (234 for Nigeria).
     *
     * USED WHEN: Inside profile.
     *
     * GIVES BACK: A https://wa.me/... link, or null when there is no number.
     */
    private function whatsapp(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = self::COUNTRY_CODE . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits;
    }

    /**
     * WHAT IT DOES: Turns a stored image path into a URL the browser can load. A full web address is
     *    left as it is. Needs `php artisan storage:link` once.
     *
     * USED WHEN: For every logo, cover and photo.
     *
     * GIVES BACK: A URL, or null when there is no image.
     */
    private function fileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : Storage::disk('public')->url($path);
    }

public function demo(): ?array
{
    $ids = config('demoSchool.school_ids', []);
    if (! $ids) {
        return null;
    }

    return [
        'schools' => DB::table('schools')->whereIn('id', $ids)->orderBy('id')->pluck('name')->all(),
        'accounts' => array_map(
            fn ($a) => $a + ['password' => config('demoSchool.password')],
            config('demoSchool.accounts', []),
        ),
    ];
}
}
