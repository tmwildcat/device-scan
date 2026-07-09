<?php

$lineWattPath = static function (?string ...$segments): string {
    $segments = array_filter(
        array_map(fn (?string $segment): string => trim((string) $segment, '/'), $segments),
        fn (string $segment): bool => $segment !== ''
    );

    return implode('/', $segments);
};

return [
    'storage_disk' => env('DEVICE_SCAN_STORAGE_DISK', env('LINEWATT_STORAGE_DISK', 'swefs')),

    'base_path' => env(
        'DEVICE_SCAN_BASE_PATH',
        env('LINEWATT_STORAGE_BASE_PATH', $lineWattPath(
            env('LINEWATT_STORAGE_ROOT', 'swefs'),
            env('LINEWATT_STORAGE_NAMESPACE', 'common'),
            env('LINEWATT_STORAGE_PRODUCT', 'line-watt-library'),
            'device-scan',
        ))
    ),
    'central_path' => env('DEVICE_SCAN_CENTRAL_PATH', '{base_path}/central'),
    'tenant_path' => env('DEVICE_SCAN_TENANT_PATH', '{base_path}/tenants/{tenant_uuid}'),
    'partner_path' => env('DEVICE_SCAN_PARTNER_PATH', '{base_path}/partners/{partner_uuid}'),

    'datasheet_folder' => env('DEVICE_SCAN_DATASHEET_FOLDER', 'datasheets'),
    'compiled_folder' => env('DEVICE_SCAN_COMPILED_FOLDER', 'compiled'),
    'review_folder' => env('DEVICE_SCAN_REVIEW_FOLDER', 'review'),

    'allowed_device_types' => ['module', 'inverter'],
    'allowed_source_types' => ['central_curated', 'tenant_private', 'partner_submitted', 'pvsyst_import'],
    'allowed_statuses' => [
        'uploaded',
        'security_checked',
        'compiling',
        'compiled',
        'failed',
        'review_required',
        'approved',
        'published',
        'rejected',
        'discontinued',
        'replaced',
    ],
];
