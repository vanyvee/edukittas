<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Six schools to work with. The first three are the DEMO schools (ids 1, 2, 3: put DEMO_SCHOOL_IDS=1,2,3
 * in .env), where the server refuses every change. The last three are ordinary schools you can edit.
 *
 * Each school has 3 classes of 5 or 6 students, 3 to 4 teachers, a principal, an ICT admin and a bursar.
 * A few people are deliberately shared so every kind of login can be tried:
 *   - John Adeyemi is the director of two demo schools, Ngozi Eze of one
 *   - Peter Nwosu teaches in two demo schools
 *   - Grace Okoro is the guardian of three children: two in one school, one in another
 *   - Aliyu Danjuma has two children in the same school, Funke Lawal has one
 *
 * Everything goes in through DB::table(), so model scopes and fillable lists never get in the way.
 * Run on an empty database: php artisan migrate:fresh --seed
 *
 * Every account has the same password. The demo accounts are listed in config/demoSchool.php.
 */
class SchoolSeeder extends Seeder
{
    private const PASSWORD = 'password';

    // key, name, link code, theme, demo?, classes, motto
    private const SCHOOLS = [
        ['dm', 'Edukittas Demo College',        'demo',        'a', true,  ['JSS1', 'JSS2', 'SS1'],                 'Learning made simple'],
        ['gf', 'Greenfield Model Academy',      'greenfield',  'b', true,  ['Primary 5', 'Primary 6', 'JSS1'],      'Growing minds, building character'],
        ['sr', 'Sunrise Nursery & Primary',     'sunrise',     'c', true,  ['Nursery 2', 'Primary 1', 'Primary 2'], 'A bright start for every child'],
        ['hc', 'Hillcrest Grammar School',      'hillcrest',   'a', false, ['JSS2', 'JSS3', 'SS2'],                 'Excellence with integrity'],
        ['un', 'Unity Comprehensive College',   'unity',       'b', false, ['JSS1', 'SS1', 'SS2'],                  'Together we achieve more'],
        ['ls', 'Little Stars Academy',          'littlestars', 'c', false, ['Nursery 1', 'Nursery 2', 'Primary 1'], 'Every child shines'],
    ];

    // name => level (the accordion's section tabs match on the level)
    private const CLASSES = [
        'Nursery 1' => 'Nursery', 'Nursery 2' => 'Nursery',
        'Primary 1' => 'Primary', 'Primary 2' => 'Primary', 'Primary 5' => 'Primary', 'Primary 6' => 'Primary',
        'JSS1' => 'Junior', 'JSS2' => 'Junior', 'JSS3' => 'Junior',
        'SS1' => 'Senior', 'SS2' => 'Senior',
    ];

    private const SUBJECTS = ['English Language', 'Mathematics', 'Basic Science', 'Social Studies', 'Civic Education', 'Computer Studies'];

    // username, role, first name, surname, sex, department, schools
    private const STAFF = [
        ['@john.director',  'director',       'John',    'Adeyemi',  'm', 'Administration', ['dm', 'gf']],
        ['@ngozi.director', 'director',       'Ngozi',   'Eze',      'f', 'Administration', ['sr']],
        ['@bello.director', 'director',       'Ibrahim', 'Bello',    'm', 'Administration', ['hc', 'un']],
        ['@okeke.director', 'director',       'Chinwe',  'Okeke',    'f', 'Administration', ['ls']],

        ['@dm.principal',   'principal',      'Amina',   'Yusuf',    'f', 'Administration', ['dm']],
        ['@dm.vp',          'vice principal', 'Chidi',   'Obi',      'm', 'Administration', ['dm']],
        ['@dm.ict',         'ictAdmin',       'Musa',    'Garba',    'm', 'ICT',            ['dm']],
        ['@dm.bursar',      'bursar',         'Bisi',    'Ojo',      'f', 'Bursary',        ['dm']],
        ['@dm.security',    'security',       'Sani',    'Abubakar', 'm', 'Administration', ['dm']],

        ['@gf.principal',   'principal',      'Emeka',   'Okonkwo',  'm', 'Administration', ['gf']],
        ['@gf.ict',         'ictAdmin',       'Tunde',   'Bello',    'm', 'ICT',            ['gf']],
        ['@gf.bursar',      'bursar',         'Ify',     'Nnamdi',   'f', 'Bursary',        ['gf']],

        ['@sr.principal',   'principal',      'Halima',  'Danladi',  'f', 'Administration', ['sr']],
        ['@sr.ict',         'ictAdmin',       'Segun',   'Alade',    'm', 'ICT',            ['sr']],
        ['@sr.bursar',      'bursar',         'Kemi',    'Ojo',      'f', 'Bursary',        ['sr']],

        ['@hc.principal',   'principal',      'Yusuf',   'Lawal',    'm', 'Administration', ['hc']],
        ['@hc.ict',         'ictAdmin',       'Chioma',  'Eze',      'f', 'ICT',            ['hc']],
        ['@hc.bursar',      'bursar',         'Femi',    'Ajayi',    'm', 'Bursary',        ['hc']],

        ['@un.principal',   'principal',      'Aisha',   'Sani',     'f', 'Administration', ['un']],
        ['@un.ict',         'ictAdmin',       'Obinna',  'Madu',     'm', 'ICT',            ['un']],
        ['@un.bursar',      'bursar',         'Tolu',    'Fashola',  'f', 'Bursary',        ['un']],

        ['@ls.principal',   'principal',      'Blessing','Udo',      'f', 'Administration', ['ls']],
        ['@ls.ict',         'ictAdmin',       'Kelechi', 'Ibe',      'm', 'ICT',            ['ls']],
        ['@ls.bursar',      'bursar',         'Hauwa',   'Musa',     'f', 'Bursary',        ['ls']],
    ];

    // username, first name, surname, sex, then  school => [subject, classes ('all' or a list)]
    private const TEACHERS = [
        ['@peter.teacher',   'Peter',   'Nwosu',   'm', ['dm' => ['English Language', 'all'], 'gf' => ['English Language', 'all']]],
        ['@jude.teacher',    'Jude',    'Ibrahim', 'm', ['dm' => ['Mathematics', ['JSS1', 'JSS2']]]],
        ['@abigail.teacher', 'Abigail', 'Johnson', 'f', ['dm' => ['Basic Science', 'all']]],
        ['@dm.teacher4',     'Kunle',   'Balogun', 'm', ['dm' => ['Computer Studies', ['SS1']]]],

        ['@gf.teacher1',     'Halima',  'Sule',    'f', ['gf' => ['Mathematics', 'all']]],
        ['@gf.teacher2',     'Obi',     'Chukwu',  'm', ['gf' => ['Basic Science', 'all']]],
        ['@gf.teacher3',     'Funmi',   'Adebayo', 'f', ['gf' => ['Social Studies', 'all']]],

        ['@sr.teacher1',     'Ngozi',   'Amadi',   'f', ['sr' => ['English Language', 'all']]],
        ['@sr.teacher2',     'Bola',    'Akin',    'f', ['sr' => ['Mathematics', 'all']]],
        ['@sr.teacher3',     'Dami',    'Okon',    'm', ['sr' => ['Civic Education', 'all']]],

        ['@hc.teacher1',     'Ahmed',   'Lamido',  'm', ['hc' => ['English Language', 'all']]],
        ['@hc.teacher2',     'Rita',    'Oyelaran','f', ['hc' => ['Mathematics', 'all']]],
        ['@hc.teacher3',     'Sola',    'Bamidele','m', ['hc' => ['Basic Science', 'all']]],
        ['@hc.teacher4',     'Nneka',   'Obi',     'f', ['hc' => ['Social Studies', ['JSS2', 'JSS3']]]],

        ['@un.teacher1',     'Gift',    'Etim',    'f', ['un' => ['English Language', 'all']]],
        ['@un.teacher2',     'Tayo',    'Ogunde',  'm', ['un' => ['Mathematics', 'all']]],
        ['@un.teacher3',     'Zainab',  'Aliyu',   'f', ['un' => ['Computer Studies', 'all']]],

        ['@ls.teacher1',     'Esther',  'Nweke',   'f', ['ls' => ['English Language', 'all']]],
        ['@ls.teacher2',     'Paul',    'Egbe',    'm', ['ls' => ['Mathematics', 'all']]],
        ['@ls.teacher3',     'Mary',    'Ekong',   'f', ['ls' => ['Civic Education', 'all']]],
    ];

    // The guardians with a story (surname, first name, sex, email, username)
    private const GUARDIANS = [
        ['%grace.okoro',    'Okoro',   'Grace', 'f', 'grace@demo.test'],
        ['%aliyu.danjuma',  'Danjuma', 'Aliyu', 'm', 'aliyu@demo.test'],
        ['%funke.lawal',    'Lawal',   'Funke', 'f', 'funke@demo.test'],
    ];

    // The children with a story, placed in a school and class: first name, surname, sex, guardian, username
    private const KIDS = [
        'dm|JSS1'      => [['Daniel',  'Okoro',   'm', '%grace.okoro',   'daniel.okoro']],
        'dm|JSS2'      => [['Amaka',   'Okoro',   'f', '%grace.okoro',   'amaka.okoro']],
        'gf|Primary 6' => [['Chidera', 'Okoro',   'm', '%grace.okoro',   'chidera.okoro']],
        'sr|Nursery 2' => [['Zainab',  'Danjuma', 'f', '%aliyu.danjuma', 'zainab.danjuma']],
        'sr|Primary 1' => [['Musa',    'Danjuma', 'm', '%aliyu.danjuma', 'musa.danjuma']],
        'dm|SS1'       => [['Tobi',    'Lawal',   'm', '%funke.lawal',   'tobi.lawal']],
    ];

    private const FIRST_M = ['Chidi', 'Emeka', 'Ibrahim', 'Tunde', 'Segun', 'Obinna', 'Femi', 'Yusuf', 'Kelechi', 'Dayo', 'Ifeanyi', 'Bayo', 'Samuel', 'David', 'Tobenna'];
    private const FIRST_F = ['Amina', 'Funke', 'Ngozi', 'Bisi', 'Halima', 'Chioma', 'Aisha', 'Tolu', 'Ify', 'Hauwa', 'Blessing', 'Kemi', 'Esther', 'Joy', 'Nneka'];
    private const SURNAMES = ['Obi', 'Lawal', 'Sani', 'Eze', 'Bello', 'Ojo', 'Nwosu', 'Adeyemi', 'Okafor', 'Musa', 'Balogun', 'Ibe', 'Udo', 'Akin', 'Chukwu', 'Danladi', 'Etim', 'Ajayi'];

    public function run(): void
    {
        $hash = Hash::make(self::PASSWORD);   // hashed once; bcrypt is slow on purpose
        $now = now();

        DB::transaction(function () use ($hash, $now) {

            // ------------------------------------------------------------
            // Reference data
            // ------------------------------------------------------------
            DB::table('lgas')->insert([
                ['name' => 'Obio/Akpor',  'state' => 'Rivers'],
                ['name' => 'Ikeja',       'state' => 'Lagos'],
                ['name' => 'Enugu North', 'state' => 'Enugu'],
            ]);
            $lgas = DB::table('lgas')->pluck('id')->all();

            DB::table('departments')->insert(array_map(fn ($n) => ['name' => $n], ['Administration', 'Academics', 'ICT', 'Bursary']));
            $dept = DB::table('departments')->pluck('id', 'name');

            DB::table('subjects')->insert(array_map(fn ($n) => ['name' => $n], self::SUBJECTS));
            $subject = DB::table('subjects')->pluck('id', 'name');

            DB::table('my_classes')->insert(array_map(
                fn ($name, $level) => ['name' => $name, 'level' => $level],
                array_keys(self::CLASSES),
                array_values(self::CLASSES),
            ));
            $class = DB::table('my_classes')->pluck('id', 'name');

            DB::table('qualifications')->insert(array_map(fn ($n) => ['qname' => $n], ['NCE', 'HND', 'B.Ed', 'B.Sc', 'PGDE', 'M.Sc']));

            // ------------------------------------------------------------
            // Schools (demo ones first, so their ids are 1, 2, 3)
            // ------------------------------------------------------------
            $schoolId = [];
            $classesOf = [];
            foreach (self::SCHOOLS as $i => [$key, $name, $code, $theme, $isDemo, $classes, $motto]) {
                $schoolId[$key] = DB::table('schools')->insertGetId([
                    'name' => $name,
                    'lg_id' => $lgas[$i % count($lgas)],
                    'dob' => (2005 + $i) . '-09-01',
                    'phone' => '0801000000' . $i,
                    'email' => "info@{$key}.demo.test",
                    'address' => ($i + 1) . ' School Road',
                    'status' => 1,
                    'current_session' => 2026,
                    'current_term' => '1',
                    'type' => 0,
                    'promotion_grade' => 45,
                    'result_template' => 'a',
                    'theme' => $theme,
                    'url_code' => $code,
                    'featured' => true,
                    'motto' => $motto,
                    'about' => "{$name} is a sample school. It shows how classes, teachers, tests, scores and results work together in one place.",
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $classesOf[$key] = $classes;
            }

            // ------------------------------------------------------------
            // Staff and teachers (logins start with @)
            // ------------------------------------------------------------
            $userId = [];
            $addUser = function (string $username, string $role, string $first, string $surname, string $sex, string $department, array $schools)
                use (&$userId, $hash, $now, $lgas, $dept, $schoolId) {
                $id = DB::table('users')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'surname' => $surname,
                    'first_name' => $first,
                    'username' => $username,
                    'role' => $role,
                    'sex' => $sex,
                    'dob' => '1985-05-15',
                    'lg_id' => $lgas[0],
                    'qualification' => 'B.Sc',
                    'phone' => '08011111111',
                    'email' => ltrim($username, '@') . '@demo.test',
                    'address' => '2 Staff Road',
                    'password' => $hash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $userId[$username] = $id;

                foreach ($schools as $key) {
                    DB::table('department_users')->insert([
                        'user_id' => $id,
                        'department_id' => $dept[$department],
                        'school_id' => $schoolId[$key],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            };

            foreach (self::STAFF as [$username, $role, $first, $surname, $sex, $department, $schools]) {
                $addUser($username, $role, $first, $surname, $sex, $department, $schools);
            }

            foreach (self::TEACHERS as [$username, $first, $surname, $sex, $teaching]) {
                $addUser($username, 'teacher', $first, $surname, $sex, 'Academics', array_keys($teaching));

                // Who teaches what: one subject per school, in the classes named (or all of them)
                foreach ($teaching as $key => [$subjectName, $classNames]) {
                    $names = $classNames === 'all' ? $classesOf[$key] : $classNames;

                    foreach ($names as $className) {
                        DB::table('class_subject_teachers')->insert([
                            'user_id' => $userId[$username],
                            'uuid' => (string) Str::uuid(),
                            'class_id' => $class[$className],
                            'subject_id' => $subject[$subjectName],
                            'school_id' => $schoolId[$key],
                            'sub_type' => 'b',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            // ------------------------------------------------------------
            // Guardians (logins start with %)
            // ------------------------------------------------------------
            $guardianId = [];
            $addGuardian = function (string $username, string $surname, string $first, string $sex, string $email)
                use (&$guardianId, $hash, $now) {
                $guardianId[$username] = DB::table('guardians')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'surname' => $surname,
                    'other_name' => $first,          // the guardians table has no first_name column
                    'username' => $username,
                    'sex' => $sex,
                    'phone' => '08022222222',
                    'email' => $email,               // the column is only 20 characters
                    'password' => $hash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            };

            foreach (self::GUARDIANS as [$username, $surname, $first, $sex, $email]) {
                $addGuardian($username, $surname, $first, $sex, $email);
            }

            // ------------------------------------------------------------
            // Students and their enrollments. Each class has 5 or 6 students: the ones with a story
            // first, then generated ones. Some of the generated students also get a guardian.
            // ------------------------------------------------------------
            $n = 0;          // running number for generated students and guardians
            $sequence = 0;   // which class this is, to alternate 5 and 6 students

            foreach (self::SCHOOLS as [$key]) {
                foreach ($classesOf[$key] as $className) {
                    $sequence++;
                    $size = 5 + ($sequence % 2);
                    $roster = self::KIDS["$key|$className"] ?? [];

                    while (count($roster) < $size) {
                        $n++;
                        $male = $n % 2 === 0;
                        $first = $male ? self::FIRST_M[$n % count(self::FIRST_M)] : self::FIRST_F[$n % count(self::FIRST_F)];
                        $surname = self::SURNAMES[($n * 3) % count(self::SURNAMES)];

                        $guardian = null;
                        if (count($roster) % 2 === 1) {      // two per class get a guardian
                            $guardian = '%g' . $n;
                            $gMale = $n % 3 === 0;
                            $gFirst = $gMale ? self::FIRST_M[($n + 4) % count(self::FIRST_M)] : self::FIRST_F[($n + 4) % count(self::FIRST_F)];
                            $addGuardian($guardian, $surname, $gFirst, $gMale ? 'm' : 'f', "g{$n}@demo.test");
                        }

                        $roster[] = [$first, $surname, $male ? 'm' : 'f', $guardian, sprintf('stu%04d', $n)];
                    }

                    foreach ($roster as [$first, $surname, $sex, $guardian, $username]) {
                        $studentId = DB::table('students')->insertGetId([
                            'uuid' => (string) Str::uuid(),
                            'surname' => $surname,
                            'first_name' => $first,
                            'username' => $username,
                            'dob' => '2013-03-10',
                            'sex' => $sex,
                            'lg_id' => $lgas[0],
                            'phone' => '08033333333',
                            'password' => $hash,
                            'guardian_id' => $guardian ? $guardianId[$guardian] : null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        DB::table('enrollments')->insert([
                            'student_id' => $studentId,
                            'school_id' => $schoolId[$key],
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
                }
            }
        });

        // ------------------------------------------------------------
        // Print the logins you will use most
        // ------------------------------------------------------------
        $rows = [];
        foreach (['@john.director', '@ngozi.director', '@dm.principal', '@peter.teacher', '%grace.okoro', '%aliyu.danjuma', 'daniel.okoro',
                  '@bello.director', '@okeke.director', '@hc.principal', '@hc.teacher1', '@ls.principal'] as $u) {
            $rows[] = [$u, self::PASSWORD];
        }
        $this->command?->info('Demo schools are ids 1, 2 and 3. Put DEMO_SCHOOL_IDS=1,2,3 in .env.');
        $this->command?->table(['Username', 'Password'], $rows);
    }
}
