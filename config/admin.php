<?php

return [
    'first_name' => env('ADMIN_FIRST_NAME', 'Admin'),
    'last_name'  => env('ADMIN_LAST_NAME', ''),
    'email'      => env('ADMIN_EMAIL'),
    'password'   => env('ADMIN_PASSWORD'),
    'role'       => (int) env('ADMIN_ROLE', 1),
];
