<?php

return [
    [
        'key' => 'dashboard',
        'icon' => 'icon-home',
        'title' => 'admin.sidebar.dashboard',
        'route' => 'admin.dashboard',
        'active' => ['admin.dashboard'],
    ],
    [
        'key' => 'ads_system',
        'icon' => 'icon-playlist_add_check',
        'title' => 'admin.sidebar.ads_system',
        'permissions_any' => [
            'ads.view',
            'ads.create',
            'ads.edit',
            'ads.delete',
            'ads.schedule',
            'screens.view',
            'screens.create',
            'screens.edit',
            'screens.delete',
            'places.view',
            'places.create',
            'places.edit',
            'places.delete',
            'monitoring.view',
            'monitoring.manage',
            'reports.view',
            'reports.generate',
            'logs.view',
            'logs.export',
        ],
        'active' => [
            'admin.ads.*',
            'admin.screens.*',
            'admin.places.*',
            'admin.monitoring.*',
            'admin.reports.*',
            'admin.logs.*',
        ],
        'children' => [
            [
                'key' => 'ads_system_all_ads',
                'title' => 'admin.sidebar.ads_system_all_ads',
                'route' => 'admin.ads.index',
                'permissions_any' => [
                    'ads.view',
                    'ads.create',
                    'ads.edit',
                    'ads.delete',
                    'ads.schedule',
                ],
                'active' => [
                    'admin.ads.index',
                    'admin.ads.create',
                    'admin.ads.edit',
                    'admin.ads.update',
                    'admin.ads.store',
                    'admin.ads.show',
                    'admin.ads.destroy',
                ],
            ],
            [
                'key' => 'ads_system_schedules',
                'title' => 'admin.sidebar.ads_system_schedules',
                'route' => 'admin.ads.index',
                'permission' => 'ads.view',
                'query' => [
                    'tab' => 'schedules',
                ],
                'active' => [
                    'admin.ads.schedules.*',
                ],
            ],
            [
                'key' => 'ads_system_screens',
                'title' => 'admin.sidebar.ads_system_screens',
                'route' => 'admin.screens.index',
                'permissions_any' => [
                    'screens.view',
                    'screens.create',
                    'screens.edit',
                    'screens.delete',
                ],
                'active' => [
                    'admin.screens.*',
                ],
            ],
            [
                'key' => 'ads_system_places',
                'title' => 'admin.sidebar.ads_system_places',
                'route' => 'admin.places.index',
                'permissions_any' => [
                    'places.view',
                    'places.create',
                    'places.edit',
                    'places.delete',
                ],
                'active' => [
                    'admin.places.*',
                ],
            ],
            [
                'key' => 'ads_system_monitoring',
                'title' => 'admin.sidebar.ads_system_monitoring',
                'route' => 'admin.monitoring.index',
                'permissions_any' => [
                    'monitoring.view',
                    'monitoring.manage',
                ],
                'active' => [
                    'admin.monitoring.*',
                ],
            ],
            [
                'key' => 'ads_system_reports',
                'title' => 'admin.sidebar.ads_system_reports',
                'route' => 'admin.reports.index',
                'permissions_any' => [
                    'reports.view',
                    'reports.generate',
                ],
                'active' => [
                    'admin.reports.*',
                ],
            ],
            [
                'key' => 'ads_system_logs',
                'title' => 'admin.sidebar.ads_system_logs',
                'route' => 'admin.logs.index',
                'permissions_any' => [
                    'logs.view',
                    'logs.export',
                ],
                'active' => [
                    'admin.logs.*',
                ],
            ],
        ],
    ],
    [
        'key' => 'admins_management',
        'icon' => 'fas fa-user-shield',
        'title' => 'admin.sidebar.admins_management',
        'permissions_any' => [
            'admins.view',
            'admins.create',
            'admins.edit',
            'admins.delete',
            'permissions.view',
            'roles.view',
        ],
        'active' => [
            'admin.admins.*',
            'admin.permissions.*',
            'admin.roles.*',
        ],
        'children' => [
            [
                'key' => 'admins_management_admins',
                'title' => 'admin.sidebar.admins',
                'route' => 'admin.admins.index',
                'permissions_any' => [
                    'admins.view',
                    'admins.create',
                    'admins.edit',
                    'admins.delete',
                ],
                'active' => [
                    'admin.admins.*',
                ],
            ],
            [
                'key' => 'admins_management_permissions',
                'title' => 'admin.sidebar.permissions',
                'route' => 'admin.permissions.index',
                'permission' => 'permissions.view',
                'active' => [
                    'admin.permissions.*',
                ],
            ],
            [
                'key' => 'admins_management_roles',
                'title' => 'admin.sidebar.roles',
                'route' => 'admin.roles.index',
                'permission' => 'roles.view',
                'active' => [
                    'admin.roles.*',
                ],
            ],
        ],
    ],
    [
        'key' => 'users_management',
        'icon' => 'fas fa-user-cog',
        'title' => 'admin.sidebar.users_management',
        'permission' => 'users.view',
        'active' => [
            'admin.users.*',
        ],
        'children' => [
            [
                'key' => 'users_management_all_users',
                'title' => 'admin.sidebar.show',
                'route' => 'admin.users.index',
                'permission' => 'users.view',
                'active' => [
                    'admin.users.index',
                ],
            ],
            [
                'key' => 'users_management_create_user',
                'title' => [
                    'en' => 'Create User',
                    'ar' => 'إنشاء مستخدم',
                ],
                'route' => 'admin.users.create',
                'permission' => 'users.view',
                'active' => [
                    'admin.users.create',
                    'admin.users.store',
                ],
            ],
        ],
    ],
    [
        'key' => 'website_cms',
        'icon' => 'fas fa-sitemap',
        'title' => 'admin.sidebar.website_cms',
        'permission' => 'cms.manage',
        'active' => [
            'admin.cms.*',
            'admin.seo_metas.*',
        ],
        'children' => [
            [
                'key' => 'website_cms_seo_metas',
                'title' => 'admin.sidebar.seo_metas',
                'route' => 'admin.seo_metas.index',
                'active' => [
                    'admin.seo_metas.*',
                ],
            ],
            [
                'key' => 'website_cms_home_page',
                'title' => 'admin.sidebar.home_page',
                'route' => 'admin.cms.home.edit',
                'active' => [
                    'admin.cms.home.*',
                ],
            ],
            [
                'key' => 'website_cms_who_we_are',
                'title' => 'admin.sidebar.who_we_are',
                'route' => 'admin.cms.whoweare.edit',
                'active' => [
                    'admin.cms.whoweare.*',
                ],
            ],
            [
                'key' => 'website_cms_contact_us',
                'title' => 'admin.sidebar.contact_us',
                'route' => 'admin.cms.contact.edit',
                'active' => [
                    'admin.cms.contact.*',
                ],
            ],
        ],
    ],
    [
        'key' => 'contact_submissions',
        'icon' => 'fas fa-inbox',
        'title' => 'admin.sidebar.contact_submissions',
        'permissions_any' => [
            'contact_submissions.view',
            'contact_submissions.delete',
        ],
        'active' => [
            'admin.contact_submissions.*',
        ],
        'children' => [
            [
                'key' => 'contact_submissions_all',
                'title' => 'admin.sidebar.all_submissions',
                'route' => 'admin.contact_submissions.index',
                'permissions_any' => [
                    'contact_submissions.view',
                    'contact_submissions.delete',
                ],
                'active' => [
                    'admin.contact_submissions.*',
                ],
            ],
        ],
    ],
];
