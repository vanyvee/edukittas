<template>
  <div class="container py-4">
    <div 
      v-for="classItem in classesData" 
      :key="classItem.classId" 
      class="card mb-5 shadow-sm"
    >
      <!-- Card Header -->
      <div class="card-header bg-primary text-white fs-5 fw-bold">
        {{ classItem.className }}
      </div>

      <!-- Card Body -->
      <div class="card-body table-responsive">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-light">
            <tr>
              <th class="text-start">Student</th>
              <th v-for="subject in classItem.subjects" :key="subject.subjectId">
                {{ subject.subjectName }}
                <div class="small text-muted">(A / T / E)</div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="student in classItem.students" 
              :key="student.studentId"
            >
              <td class="text-start fw-semibold">{{ student.studentName }}</td>

              <!-- Loop through subjects -->
              <td v-for="subject in classItem.subjects" :key="subject.subjectId">
                <template v-if="findGrade(student, subject.subjectId)">
                  <span class="text-success fw-bold">
                    {{ findGrade(student, subject.subjectId).assignment }}
                  </span> /
                  <span class="text-info fw-bold">
                    {{ findGrade(student, subject.subjectId).test }}
                  </span> /
                  <span class="text-danger fw-bold">
                    {{ findGrade(student, subject.subjectId).exam }}
                  </span>
                </template>
                <template v-else>
                  <span class="text-muted">-</span>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
// ✅ Mock Data (replace with API call later)
const classesData = [
  {
    classId: "class_1",
    className: "Grade 10A",
    subjects: [
      { subjectId: "sub_101", subjectName: "Mathematics" },
      { subjectId: "sub_102", subjectName: "English" }
    ],
    students: [
      {
        studentId: "stu_001",
        studentName: "Alice Johnson",
        subjects: [
          { subjectId: "sub_101", grade: { assignment: 85, test: 88, exam: 92 } },
          { subjectId: "sub_102", grade: { assignment: 90, test: 85, exam: 95 } }
        ]
      },
      {
        studentId: "stu_002",
        studentName: "Bob Smith",
        subjects: [
          { subjectId: "sub_101", grade: { assignment: 79, test: 81, exam: 86 } }
        ]
      }
    ]
  }
]

// ✅ Helper: find grade by subjectId for a student
function findGrade(student, subjectId) {
  const subject = student.subjects.find(s => s.subjectId === subjectId)
  return subject ? subject.grade : null
}
</script>

<style scoped>
.card {
  border-radius: 1rem;
  overflow: hidden;
}

.table th, .table td {
  vertical-align: middle;
}

.table thead th {
  background-color: #f8f9fa;
}

.text-success {
  color: #28a745 !important;
}
.text-info {
  color: #0dcaf0 !important;
}
.text-danger {
  color: #dc3545 !important;
}

.card-header {
  background: linear-gradient(135deg, #007bff, #0056b3);
}

.table td {
  font-size: 0.95rem;
}
</style>






my backend sql/storedprocedure retrieval

$rows = DB::select('CALL GetAllGrades()');
$collection = collect($rows);


$structured = $collection
    ->groupBy('class_name')
    ->map(function ($classGroup) {
        return $classGroup->groupBy('student_name')->map(function ($studentGroup) {
            return $studentGroup->map(function ($item) {
                return [
                    'subject_name' => $item->subject_name,
                    'assignment' => $item->assignment,
                    'test' => $item->test,
                    'exam' => $item->exam,
                ];
            });
        });
    });

return response()->json($structured);



{
  "JSS1": {
    "John": [
      { "subject_name": "Math", "assignment": 10, "test": 20, "exam": 60 },
      { "subject_name": "English", "assignment": 9, "test": 18, "exam": 58 }
    ],
    "Sarah": [
      { "subject_name": "Math", "assignment": 8, "test": 19, "exam": 55 }
    ]
  }
}




assuming i groupby class only and foeward to frontend
this is how javascript will do the grouping
const groupedData = rawData.map(classItem => {
  const studentMap = {};

  classItem.students.forEach(record => {
    if (!studentMap[record.student_id]) {
      studentMap[record.student_id] = {
        student_id: record.student_id,
        student_name: record.student_name,
        subjects: []
      };
    }

    studentMap[record.student_id].subjects.push({
      subject_id: record.subject_id,
      subject_name: record.subject_name,
      assignment: record.assignment,
      test: record.test,
      exam: record.exam
    });
  });

  return {
    class_id: classItem.class_id,
    class_name: classItem.class_name,
    students: Object.values(studentMap)
  };
});

console.log(groupedData);



out put
[
  {
    "class_id": 1,
    "class_name": "JSS 1",
    "students": [
      {
        "student_id": 1,
        "student_name": "John Doe",
        "subjects": [
          { "subject_id": 1, "subject_name": "Mathematics", "assignment": 15, "test": 20, "exam": 60 },
          { "subject_id": 2, "subject_name": "English", "assignment": 17, "test": 19, "exam": 55 }
        ]
      },
      {
        "student_id": 2,
        "student_name": "Mary Jane",
        "subjects": [
          { "subject_id": 1, "subject_name": "Mathematics", "assignment": 12, "test": 18, "exam": 50 },
          { "subject_id": 2, "subject_name": "English", "assignment": 20, "test": 16, "exam": 60 }
        ]
      }
    ]
  }
]


and heee is how view integration looks
<div v-for="(students, className) in grouped" :key="className" class="mb-4">
  <h5>{{ className }}</h5>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Student</th>
        <th>Subject</th>
        <th>Assignment</th>
        <th>Test</th>
        <th>Exam</th>
      </tr>
    </thead>
    <tbody>
      <template v-for="(subjects, studentName) in students" :key="studentName">
        <tr v-for="subject in subjects" :key="subject.subject_name">
          <td>{{ studentName }}</td>
          <td>{{ subject.subject_name }}</td>
          <td>{{ subject.assignment }}</td>
          <td>{{ subject.test }}</td>
          <td>{{ subject.exam }}</td>
        </tr>
      </template>
    </tbody>
  </table>
</div>