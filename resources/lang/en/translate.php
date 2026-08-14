<?php
return [
    /*
     * Chrome shared by every public page. `switch_language` is the accessible name of
     * the language control, so it is written in THIS file's language and describes where
     * the link goes: an English reader is being offered Arabic.
     */
    'layout' => [
        'switch_language' => 'Switch language to Arabic',
        'toggle_navigation' => 'Toggle navigation',
        // Accessible name for the footer's embedded map. An <iframe> with no title is
        // announced as "frame" by a screen reader; the destination itself is configured
        // in Settings, so only the label belongs here.
        'map_title' => 'Our location on the map',
        // Accessible name for a social link. The icon is aria-hidden, so this is the only
        // thing a screen reader announces for it.
        'social_link' => 'Breem on :platform',
    ],

    'homepage' => [
        'buttons' => [
            'read_more' => 'Read More',
            'subscribe' => 'Subscribe',
            'view_all' => 'View All',
            'apply_now' => 'Apply Now',
            'upload_now' => 'Upload Now',
            'follow_us' => 'Follow Us',
            'send' => 'Send',
            'submit' => 'Submit',
            'download_b' => 'Download Brochure',
            'filter' => 'PRODUCTS FILTER',
        ],
        'placeholder' => [
            'first_name' => 'Enter your first name',
            'last_name' => 'Enter your last name',
            'email' => 'Enter your email address',
            'phone' => 'Enter your phone number',
            'select_department' => 'Select Department ...!!',
            'message' => 'Enter your question or message',
            'company_name' => 'Enter your company name',
            'full_name' => 'Enter your full name',
        ]
    ]
];
