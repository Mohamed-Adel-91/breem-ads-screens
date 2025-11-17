<?php

return [
    'first_name' => env('ADMIN_FIRST_NAME', 'Admin'),
    'last_name'  => env('ADMIN_LAST_NAME', ''),
    'email'      => env('ADMIN_EMAIL'),
    'password'   => env('ADMIN_PASSWORD'),
    'cms_first_name' => env('CMS_ADMIN_FIRST_NAME', 'CMS-Admin'),
    'cms_last_name'  => env('CMS_ADMIN_LAST_NAME', ''),
    'cms_email'      => env('CMS_ADMIN_EMAIL'),
    'cms_password'   => env('CMS_ADMIN_PASSWORD'),
];
