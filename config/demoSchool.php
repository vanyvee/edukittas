<?php

return [

    // The demo schools' ids in the schools table: DEMO_SCHOOL_IDS=1,2,3 in .env (the first three schools SchoolSeeder creates).
    // Inside these schools the server refuses every request that would change data.
    'school_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('DEMO_SCHOOL_IDS', ''))))),

    // Shown on the guest page's demo screen. These accounts are created by SchoolSeeder, so keep the two in step.
    'password' => 'password',

    'accounts' => [
        // group,           label,                                                                            username
        ['group' => 'Directors',     'label' => 'Login as Mr John Adeyemi, a director of 2 schools',                  'username' => '@john.director'],
        ['group' => 'Directors',     'label' => 'Login as Mrs Ngozi Eze, a director of 1 school',                     'username' => '@ngozi.director'],

        ['group' => 'School leaders', 'label' => 'Login as Mrs Amina Yusuf, principal of Edukittas Demo College',      'username' => '@dm.principal'],
        ['group' => 'School leaders', 'label' => 'Login as Mr Chidi Obi, vice principal of Edukittas Demo College',    'username' => '@dm.vp'],
        ['group' => 'School leaders', 'label' => 'Login as Mr Tunde Bello, ICT admin of Greenfield Model Academy',     'username' => '@gf.ict'],
        ['group' => 'School leaders', 'label' => 'Login as Mrs Kemi Ojo, bursar of Sunrise Nursery & Primary',         'username' => '@sr.bursar'],

        ['group' => 'Teachers',      'label' => 'Login as Mr Peter Nwosu, a teacher in 2 schools',                    'username' => '@peter.teacher'],
        ['group' => 'Teachers',      'label' => 'Login as Mrs Abigail Johnson, a teacher with 3 classes',             'username' => '@abigail.teacher'],
        ['group' => 'Teachers',      'label' => 'Login as Mr Jude Ibrahim, a teacher with 2 classes',                 'username' => '@jude.teacher'],
        ['group' => 'Teachers',      'label' => 'Login as Mr Kunle Balogun, a teacher with 1 class',                  'username' => '@dm.teacher4'],

        ['group' => 'Guardians',     'label' => 'Login as Mrs Grace Okoro, a guardian of 3 kids in 2 different schools', 'username' => '%grace.okoro'],
        ['group' => 'Guardians',     'label' => 'Login as Mr Aliyu Danjuma, a guardian of 2 kids in the same school', 'username' => '%aliyu.danjuma'],
        ['group' => 'Guardians',     'label' => 'Login as Mrs Funke Lawal, a guardian of 1 kid',                      'username' => '%funke.lawal'],

        ['group' => 'Students',      'label' => 'Login as Daniel Okoro, a student in JSS1',                           'username' => 'daniel.okoro'],
        ['group' => 'Students',      'label' => 'Login as Tobi Lawal, a student in SS1',                              'username' => 'tobi.lawal'],

        ['group' => 'Other staff',   'label' => 'Login as Mr Sani Abubakar, security officer (limited access)',       'username' => '@dm.security'],
    ],

];
