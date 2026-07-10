<?php

return [
    'zambia_provinces' => [
        'Central',
        'Copperbelt',
        'Eastern',
        'Luapula',
        'Lusaka',
        'Muchinga',
        'Northern',
        'North-Western',
        'Southern',
        'Western',
    ],

    'grade_levels' => [
        'primary' => range(1, 7),
        'junior_secondary' => [8, 9],
        'senior_secondary' => [10, 11, 12],
    ],

    'ecz_grades' => [7, 9, 12],

    'terms_per_year' => 3,

    'grading_scale' => [
        ['min' => 75, 'max' => 100, 'letter' => 'A', 'label' => 'Distinction'],
        ['min' => 65, 'max' => 74,  'letter' => 'B', 'label' => 'Merit'],
        ['min' => 50, 'max' => 64,  'letter' => 'C', 'label' => 'Credit'],
        ['min' => 40, 'max' => 49,  'letter' => 'D', 'label' => 'Pass'],
        ['min' => 0,  'max' => 39,  'letter' => 'F', 'label' => 'Fail'],
    ],
];
