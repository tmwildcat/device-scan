<?php

$lineWattPath = static function (?string ...$segments): string {
    $segments = array_filter(
        array_map(fn (?string $segment): string => trim((string) $segment, '/'), $segments),
        fn (string $segment): bool => $segment !== ''
    );

    return implode('/', $segments);
};

return [
    'disk' => env('LINEWATT_STORAGE_DISK', 'swefs'),
    'root' => env('LINEWATT_STORAGE_ROOT', 'swefs'),
    'namespace' => env('LINEWATT_STORAGE_NAMESPACE', 'common'),
    'product' => env('LINEWATT_STORAGE_PRODUCT', 'line-watt-library'),
    'environment' => env('LINEWATT_STORAGE_ENV', env('APP_ENV', 'local')),

    'base_path' => env(
        'LINEWATT_STORAGE_BASE_PATH',
        $lineWattPath(
            env('LINEWATT_STORAGE_ROOT', 'swefs'),
            env('LINEWATT_STORAGE_NAMESPACE', 'common'),
            env('LINEWATT_STORAGE_PRODUCT', 'line-watt-library')
        )
    ),
];
