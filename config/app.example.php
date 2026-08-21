<?php
declare(strict_types=1);

/*
 * Copy to config/app.php on the server and fill real values.
 * Do NOT commit config/app.php.
 */
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'phoohotshu',
        'user' => 'CHANGE_ME',
        'pass' => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],

    'security' => [
        // Generate a long random secret, e.g. 64+ chars.
        'app_secret' => 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET',
        'max_requests_per_10_min' => 8,
    ],

    'admin' => [
        // Create a password hash with:
        // php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
        'username' => 'admin',
        'password_hash' => 'CHANGE_ME_PASSWORD_HASH',
        'session_name' => 'PHOOHOTSHU_ADMIN',
        'session_lifetime_seconds' => 28800,
    ],

    'email' => [
        // Notification is secondary. RFQ success depends on DB commit, not email delivery.
        'enabled' => false,
        'provider' => 'resend',
        'resend_api_key' => 'CHANGE_ME',
        'from' => 'RFQ <no-reply@phoohotshu.tw>',
        'to' => 'CHANGE_ME@example.com',
    ],

    'uploads' => [
        // Prefer a directory outside public_html when possible.
        // If kept inside this project, .htaccess below denies all web access.
        'dir' => dirname(__DIR__) . '/storage/private_uploads',
        'max_files' => 5,
        'max_file_bytes' => 8 * 1024 * 1024,
        'allowed_mime' => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ],
    ],
];
