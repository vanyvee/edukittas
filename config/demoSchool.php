

<?php

return [

    // The demo school's id in the schools table. Put DEMO_SCHOOL_ID=1 in .env (1 is the id SchoolSeeder gives it).
    // While this is set, every request that would change data is refused for people working in that school.
    'school_id' => env('DEMO_SCHOOL_ID'),

    // Shown on the guest page's demo screen. These must match the accounts SchoolSeeder creates.
    'password' => 'password',

    'accounts' => [
        ['label' => 'Director',                 'username' => '@director'],
        ['label' => 'Principal',                'username' => '@principal'],
        ['label' => 'ICT admin',                'username' => '@ictadmin'],
        ['label' => 'Bursar',                   'username' => '@bursar'],
        ['label' => 'Teacher (English)',        'username' => '@teacher1'],
        ['label' => 'Teacher (Mathematics)',    'username' => '@teacher2'],
        ['label' => 'Guardian (two children)',  'username' => '%guardian1'],
        ['label' => 'Student',                  'username' => 'stu001'],
    ],

];
