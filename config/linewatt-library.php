<?php

return [
    'debug' => filter_var(env('LINEWATT_LIB_DEBUG', false), FILTER_VALIDATE_BOOL),

    'locales' => [
        'en' => 'English',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
        'ar' => 'العربية',
        'pt' => 'Português',
        'zh' => '中文',
        'ja' => '日本語',
    ],

    'upload' => [
        'max_pdf_size_mb' => (int) env('LINEWATT_UPLOAD_MAX_PDF_SIZE_MB', 25),
        'allowed_extensions' => ['pdf'],
        'allowed_mime_types' => ['application/pdf'],
        'malware_scan' => [
            'enabled' => filter_var(env('LINEWATT_MALWARE_SCAN_ENABLED', false), FILTER_VALIDATE_BOOL),
            'driver' => env('LINEWATT_MALWARE_SCAN_DRIVER', 'null'),
            'fail_closed' => filter_var(env('LINEWATT_MALWARE_SCAN_FAIL_CLOSED', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),
        ],
    ],

    'storage_quotas_mb' => [
        'none' => 100,
        'registered' => 100,
        'subscriber' => 1024,
        'demo_member' => 1024,
        'pro' => 5120,
        'enterprise' => 51200,
        'manufacturer_pro' => 5120,
        'manufacturer_enterprise' => 51200,
        'default' => 1024,
    ],
];
