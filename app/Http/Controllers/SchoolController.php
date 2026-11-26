<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Supports\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;



use App\Helpers\FileUploadHelper;
use App\Http\Requests\SchoolRequest;
use App\Models\Department;
use App\Services\SchoolService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SchoolController extends Controller
{


public function __construct(private readonly SchoolService $schools)
    {
    }

    /** GET /public/lgas -> [{ id, name, state }] */
    public function lgas(): JsonResponse
    {
        return response()->json(DB::table('lgas')->orderBy('state')->orderBy('name')->get(['id', 'name', 'state']));
    }

    /** GET /public/departments -> [{ id, name }] */
    public function departments(): JsonResponse
    {
        return response()->json(Department::orderBy('name')->get(['id', 'name']));
    }

    /**
     * WHAT IT DOES: Saves the uploaded images, then creates the school and its first user together.
     *    If the database write fails, the images are removed again so nothing is left behind.
     *
     * USED WHEN: POST /public/schools/register (multipart form).
     *
     * GIVES BACK: 201 { message, school: { id, name }, username }
     */
    public function store(SchoolRequest $request): JsonResponse
    {
        $rule = fn (int $mb, array $min) => ['image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'maxSizeMB'  => $mb,
            'dimensions' => ['min' => $min],
        ]];

        $saved = [];      // every path we write, so a failure can undo them
        $rejected = [];

        try {
            $logo     = FileUploadHelper::uploadOne($request, 'logo', 'schools/logos', $rule(2, [100, 100]), $rejected);
            $cover    = FileUploadHelper::uploadOne($request, 'pix', 'schools/covers', $rule(5, [600, 300]), $rejected);
            $passport = FileUploadHelper::uploadOne($request, 'passport', 'users/passports', $rule(2, [150, 150]), $rejected);
            $photos   = array_column(FileUploadHelper::uploadFile($request, 'photos', 'schools/photos', $rule(5, [400, 300]), $rejected), 'path');

            $saved = array_values(array_filter([$logo, $cover, $passport, ...$photos]));

            if ($rejected) {
                $this->forget($saved);
                return response()->json(['message' => 'Some images were not accepted.', 'errors' => ['logo' => $rejected]], 422);
            }

            $data   = $request->validated();
            $school = $data['school'];
            $user   = $data['user'];

            $school['theme']           ??= 'default';
            $school['result_template'] ??= 'a';
            $school['logo']   = $logo;
            $school['pix']    = $cover;
            $school['photos'] = $photos ?: null;
            $user['passport'] = $passport;
            unset($user['password_confirmation']);

            $result = $this->schools->register([
                'school'         => $school,
                'user'           => $user,
                'department_ids' => $data['department_ids'],
            ]);
        } catch (Throwable $e) {
            $this->forget($saved);
            Log::error('School registration failed: ' . $e->getMessage());

            return response()->json(['message' => 'We could not register the school. Please try again.'], 500);
        }

        return response()->json([
            'message'  => 'School registered.',
            'school'   => ['id' => $result['school']->id, 'name' => $result['school']->name],
            'username' => $result['user']->username,
        ], 201);
    }

    private function forget(array $paths): void
    {
        if ($paths) Storage::disk('public')->delete($paths);
    }
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