<?php

$localPassword = in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)
    ? 'password'
    : null;

return [
    'passwords' => [
        'admin' => env('DEMO_ADMIN_PASSWORD', $localPassword),
        'lecturer' => env('DEMO_LECTURER_PASSWORD', $localPassword),
        'lecturer_2' => env('DEMO_LECTURER_2_PASSWORD', $localPassword),
        'student' => env('DEMO_STUDENT_PASSWORD', $localPassword),
        'idor_student' => env('DEMO_IDOR_STUDENT_PASSWORD', $localPassword),
    ],
];
