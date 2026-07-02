table

id
classId
schId
subkectId
createdAt
expiryDate
text
json...file nulable


gradetable ....assignment
type json
structure... assigntId, score,remark,
total, textanswer, file[]



// In Grade.php
protected $casts = [
    'assignments' => 'array',
];



public function scoreAssignments(Request $request)
{
    $data = $request->input('scores'); 
    // array of {student_id, class_id, subject_id, assignment_id, score, total, remark (optional)}

    foreach ($data as $record) {

        $grade = Grade::where('classId', $record['classId'])
                      ->where('subjectId', $record['subjectId'])
                      ->where('studentId', $record['studentId'])
                      ->first();

        if (!$grade) continue;

        $assignments = $grade->assignments ?? [];

        // Handle optional remark
        $remarkPath = null;
        if ($request->hasFile("remark_{$record['assignment_id']}")) {
            // File remark (e.g., audio)
            $file = $request->file("remark_{$record['assignment_id']}");
            $allowed = ['mp3', 'wav', 'm4a', 'ogg'];
            $ext = strtolower($file->getClientOriginalExtension());

            if (in_array($ext, $allowed)) {
                $remarkPath = $file->store('uploads/remarks', 'public');
            } else {
                continue; // skip invalid file type
            }
        } elseif (!empty($record['remark'])) {
            // Text remark
            $remarkPath = trim($record['remark']);
        }

        // Update or create the assignment entry
        $assignId = $record['assignment_id'];
        $assignments[$assignId] = [
            'score'   => $record['score'],
            'total'   => $record['total'],
            'answer'  => $record['answer'] ?? '',
            'files'   => $record['files'] ?? [],
            'remark'  => $remarkPath,
        ];

        // Save updated assignments JSON
        // try change this save style
        $grade->assignments = $assignments;
        $grade->save();
    }

    return response()->json(['message' => 'Scores and remarks recorded successfully']);
}

public function submitAssignments(Request $request)
{
    // Student is submitting one assignment for a subject
    $assignId  = $request->input('assignment_id');
    $classId   = $request->input('class_id');
    $subjectId = $request->input('subject_id');
    $answer    = $request->input('answer');
    $total     = $request->input('total', null); // optional — can be null for now

    // Handle file uploads
    $filesData = [];
    if ($request->hasFile('files')) {
        foreach ($request->file('files') as $index => $file) {
            $path = $file->store('uploads', 'public');
            $filesData[] = [
                'title' => $request->input("files.$index.title") ?? $file->getClientOriginalName(),
                'path'  => $path,
                'type'  => $file->getClientOriginalExtension(),
            ];
        }
    }

    // Find the grade record for this student & subject
    $grade = Grade::where('classId', $classId)
                  ->where('subjectId', $subjectId)
                  ->where('studentId', auth()->id())
                  ->first();

    if (!$grade) {
        return response()->json(['message' => 'Grade record not found'], 404);
    }

    // Decode or initialize assignments JSON
    $assignments = $grade->assignments ?? [];

    // Map by assignment ID
    $assignments[$assignId] = [
        'score'  => $assignments[$assignId]['score'] ?? '', // keep existing score if any
        'total'  => $total ?? $assignments[$assignId]['total'] ?? '',
        'answer' => $answer,
        'files'  => $filesData,
    ];

    // Save updated JSON usinging this style stops observer from lunch
    DB::table('grades')
    ->where('id', $grade->id)
    ->update(['assignments' => json_encode($assignments)]);

    return response()->json(['message' => 'Assignment submitted successfully']);
}


public function store(Request $request)
{
    // Validate incoming request data
    $data = $request->validate([
        'teacher_id' => 'required|integer',
        'asgn_id' => 'required|integer',
        'text' => 'required|string',
        'class_id' => 'required|integer',
        'subject_id' => 'required|integer',
        'student_ids' => 'nullable|array', // Optional, for selective students
        'files' => 'nullable|array',
        'files.*.title' => 'required_with:files|string',
        'files.*.file' => 'required_with:files|file',
    ]);

    // Handle file uploads, if any
    $filesData = [];
    if ($request->hasFile('files')) {
        foreach ($request->file('files') as $index => $file) {
            $path = $file->store('uploads', 'public');
            $filesData[] = [
                'title' => $request->files[$index]['title'],
                'path'  => $path,
                'type'  => $file->getClientOriginalExtension(),
            ];
        }
    }

    // Step 1: Insert into the assignment_questions table
    DB::table('assignment_questions')->insert([
        'teacher_id' => $data['teacher_id'],
        'asgn_id' => $data['asgn_id'],
        'text' => $data['text'],
        'files' => json_encode($filesData),
    ]);

    // Step 2: Select students based on class, subject, or specific student_ids
    $students = Grade::where('classId', $data['class_id'])
                     ->where('subjectId', $data['subject_id']);

    if (!empty($data['student_ids'])) {
        $students = $students->whereIn('studentId', $data['student_ids']);
    }

    $students = $students->get(); // Fetch the students

    // Step 3: Update the `assignments` column for each student
    foreach ($students as $grade) {
        $assignments = $grade->assignments ?? [];

        // Populate the grade's assignments column with the new assignment
        $assignments[$data['asgn_id']] = [
            'score'   => 0,
            'total'   => 0,
            'answer'  => '',
            'files'   => $filesData, // Associated files, if any
            'remark'  => '', // Empty initial remark
        ];

        // Save the updated assignments JSON in the grade table
    $grade->withoutEvents(function () use ($grade) {
    $grade->assignments = $assignments;
    $grade->save();  // This won't trigger the observer now
});
    }

    return response()->json(['success' => true]);
}