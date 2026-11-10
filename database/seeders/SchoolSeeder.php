<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One complete school to work with, and later to use as the read-only demo school.
 *
 * Everything goes in through DB::table(), so model scopes, fillable lists and boot hooks (Enrollment's
 * school scope needs a session) never get in the way. Run on an empty database:
 *   php artisan migrate:fresh --seed
 *
 * Every account gets the same password, printed at the end together with the usernames.
 */
class SchoolSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $hash = Hash::make(self::PASSWORD);   // hashed once; bcrypt is slow on purpose
        $now = now();

        DB::transaction(function () use ($hash, $now) {

            // ------------------------------------------------------------
            // Reference data
            // ------------------------------------------------------------
            DB::table('lgas')->insert([
                ['name' => 'Obio/Akpor', 'state' => 'Rivers'],
                ['name' => 'Ikeja',      'state' => 'Lagos'],
                ['name' => 'Enugu North', 'state' => 'Enugu'],
            ]);
            $lgId = DB::table('lgas')->value('id');

            DB::table('departments')->insert(array_map(fn ($n) => ['name' => $n],
                ['Administration', 'Academics', 'ICT', 'Bursary']));
            $dept = DB::table('departments')->pluck('id', 'name');

            DB::table('subjects')->insert(array_map(fn ($n) => ['name' => $n],
                ['English Language', 'Mathematics', 'Basic Science', 'Social Studies', 'Civic Education', 'Computer Studies']));
            $subject = DB::table('subjects')->pluck('id', 'name');

            DB::table('my_classes')->insert([
                ['name' => 'JSS1', 'level' => 'Junior'],
                ['name' => 'JSS2', 'level' => 'Junior'],
                ['name' => 'JSS3', 'level' => 'Junior'],
            ]);
            $class = DB::table('my_classes')->pluck('id', 'name');

            DB::table('qualifications')->insert(array_map(fn ($n) => ['qname' => $n],
                ['NCE', 'HND', 'B.Ed', 'B.Sc', 'PGDE', 'M.Sc']));

            // ------------------------------------------------------------
            // The school
            // ------------------------------------------------------------
            $schoolId = DB::table('schools')->insertGetId([
                'name' => 'Edukittas Demo College',
                'lg_id' => $lgId,
                'dob' => '2010-09-01',
                'phone' => '08000000000',
                'email' => 'demo@edukittas.test',
                'address' => '1 Demo Street',
                'status' => 1,
                'current_session' => 2026,
                'current_term' => '1',
                'type' => 0,
                'promotion_grade' => 45,
                'result_template' => 'a',
                'theme' => 'a',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // ------------------------------------------------------------
            // Staff (logins start with @)
            // ------------------------------------------------------------
            $staff = [
                // username,    role,        surname,   first,    sex, department
                ['@director',  'director',  'Adeyemi', 'Samuel',  'm', 'Administration'],
                ['@principal', 'principal', 'Okafor',  'Grace',   'f', 'Administration'],
                ['@ictadmin',  'ictAdmin',  'Bello',   'Musa',    'm', 'ICT'],
                ['@bursar',    'bursar',    'Eze',     'Ngozi',   'f', 'Bursary'],
                ['@teacher1',  'teacher',   'Nwosu',   'Peter',   'm', 'Academics'],
                ['@teacher2',  'teacher',   'Ibrahim', 'Jude',    'm', 'Academics'],
                ['@teacher3',  'teacher',   'Johnson', 'Abigail', 'f', 'Academics'],
            ];

            $userId = [];
            foreach ($staff as [$username, $role, $surname, $first, $sex, $department]) {
                $id = DB::table('users')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'surname' => $surname,
                    'first_name' => $first,
                    'username' => $username,
                    'role' => $role,
                    'sex' => $sex,
                    'dob' => '1985-05-15',
                    'lg_id' => $lgId,
                    'qualification' => 'B.Sc',
                    'phone' => '08011111111',
                    'email' => ltrim($username, '@') . '@demo.test',
                    'address' => '2 Staff Road',
                    'password' => $hash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $userId[$username] = $id;

                DB::table('department_users')->insert([
                    'user_id' => $id,
                    'department_id' => $dept[$department],
                    'school_id' => $schoolId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ------------------------------------------------------------
            // Who teaches what (class_subject_teachers)
            // ------------------------------------------------------------
            $teaching = [
                '@teacher1' => 'English Language',
                '@teacher2' => 'Mathematics',
                '@teacher3' => 'Basic Science',
            ];
            foreach ($teaching as $username => $subjectName) {
                foreach (['JSS1', 'JSS2'] as $className) {
                    DB::table('class_subject_teachers')->insert([
                        'user_id' => $userId[$username],
                        'uuid' => (string) Str::uuid(),
                        'class_id' => $class[$className],
                        'subject_id' => $subject[$subjectName],
                        'school_id' => $schoolId,
                        'sub_type' => 'b',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // ------------------------------------------------------------
            // Guardians (logins start with %). Guardian 1 has two wards in different classes,
            // which exercises the ward picker.
            // ------------------------------------------------------------
            $guardianId = [];
            foreach ([
                ['%guardian1', 'Okoro', 'Chika', 'f'],
                ['%guardian2', 'Danjuma', 'Aliyu', 'm'],
            ] as [$username, $surname, $other, $sex]) {
                $guardianId[$username] = DB::table('guardians')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'surname' => $surname,
                    'other_name' => $other,
                    'username' => $username,
                    'sex' => $sex,
                    'phone' => '08022222222',
                    'email' => ltrim($username, '%') . '@demo.test',   // the column is only 20 characters
                    'password' => $hash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ------------------------------------------------------------
            // Students (no prefix) and their enrollments
            // ------------------------------------------------------------
            $students = [
                // username, surname,   first,    sex, class,  guardian
                ['stu001', 'Okoro',   'Daniel',  'm', 'JSS1', '%guardian1'],
                ['stu002', 'Okoro',   'Amaka',   'f', 'JSS2', '%guardian1'],
                ['stu003', 'Danjuma', 'Zainab',  'f', 'JSS1', '%guardian2'],
                ['stu004', 'Obi',     'Emeka',   'm', 'JSS1', null],
                ['stu005', 'Lawal',   'Funke',   'f', 'JSS2', null],
                ['stu006', 'Sani',    'Ibrahim', 'm', 'JSS2', null],
            ];

            foreach ($students as [$username, $surname, $first, $sex, $className, $guardian]) {
                $id = DB::table('students')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'surname' => $surname,
                    'first_name' => $first,
                    'username' => $username,
                    'dob' => '2013-03-10',
                    'sex' => $sex,
                    'lg_id' => $lgId,
                    'phone' => '08033333333',
                    'password' => $hash,
                    'guardian_id' => $guardian ? $guardianId[$guardian] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('enrollments')->insert([
                    'student_id' => $id,
                    'school_id' => $schoolId,
                    'class_id' => $class[$className],
                    'status' => '1',
                    'admission_date' => $now->toDateString(),
                    'sub_type' => 'b',
                    'arm' => 'A',
                    'post' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        // ------------------------------------------------------------
        // Print the logins
        // ------------------------------------------------------------
        $rows = [];
        foreach (['@director', '@principal', '@ictadmin', '@bursar', '@teacher1', '@teacher2', '@teacher3', '%guardian1', '%guardian2', 'stu001', 'stu004'] as $u) {
            $rows[] = [$u, self::PASSWORD];
        }
        $this->command?->table(['Username', 'Password'], $rows);
    }
}
