<?php
/** General application settings (no credentials here). */
return [
    'app_name'        => 'LakbayLokal',
    // Leave empty to auto-detect (works for http://localhost/LakbayLokal).
    // Set manually, e.g. '/LakbayLokal', only if links come out wrong.
    'base_url'        => '',
    'debug'           => false,          // true = show error details (local development only)
    'currency_symbol' => '₱',
    'currency_code'   => 'PHP',
    'per_page'        => 9,              // public destination listing
    'admin_per_page'  => 10,             // admin tables
    'upload_max_bytes'=> 5 * 1024 * 1024,
    'session_name'    => 'LLSESSID',
    'timezone'        => 'Asia/Manila',
    'max_travelers'   => 20,
    // Editable placeholders: replace with real details when supplied.
    'contact' => [
        'email'   => 'lakbaylokal@gmail.com',
        'phone'   => '0951-594-7041',
        'address' => '9th Ave, Grace Park, Caloocan City, Philippines',
    ],
];
