<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    public function isGuardianAlreadySet(Request $request)
{
    $match =  Guardian::where('email', $request->email)->first();
    if ($match) {
        session()->put('guardian_id', $match->id);
    }

    return response()->json([
        'matched'  => (bool) $match,
        'guardian' => $match ? ['pix' => $match->pix, 'phone' => $match->phone] : null,
    ]);
}


public function confirmIsGuardianAlreadySet()
{
    abort_unless(session('guardian_id'), 404, 'No matching guardian found to confirm.');
        session()->put('guardian_confirmed', true); 
        return response()->json(['confirmed' => true]);    
}





public function checkIfStudentAlreadyExists(Request $request): JsonResponse
{
    $data = $request->only(['surname', 'first_name', 'other_name', 'dob']);
    $submittedFingerprint = $this->nameFingerprint($data);

    $students = Student::whereDate('dob', $data['dob'])->get();

    $matches = $students->filter(
        fn ($student) => $this->nameFingerprint($student->toArray()) === $submittedFingerprint
    );

    if ($matches->count() === 1) {
        session()->put('student_id', $matches->first()->id);

        return response()->json([
            'status'  => 'match',
            'student' => [
                'pix'        => $matches->first()->pix,
                'surname'    => $matches->first()->surname,
                'first_name' => $matches->first()->first_name,
            ],
        ]);
    }

    if ($matches->count() > 1) {
        return response()->json([
            'status'     => 'ambiguous',
            'candidates' => $matches->map(fn ($s) => [
                'id'         => $s->id,
                'pix'        => $s->pix,
                'surname'    => $s->surname,
                'first_name' => $s->first_name,
            ]),
        ]);
    }

    return response()->json(['status' => 'none']);
}


public function confirmStudentAlreadySet()
{
    abort_unless(session('student_id'), 404, 'No matching student found to confirm.');
        session()->put('student_confirmed', true); 
        return response()->json(['confirmed' => true]);
    
}

/**
 * Create a normalized fingerprint from the student's
 * surname, first name and other name.
 */
protected function nameFingerprint(array $data): string
{
    return collect([
        $data['surname'] ?? null,
        $data['first_name'] ?? null,
        $data['other_name'] ?? null,
    ])
        ->filter()
        ->map(fn ($name) => strtolower(trim($name)))
        ->sort()
        ->values()
        ->implode(' ');
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        //
    }
}
