<?php

use App\Http\Controllers\DeviceScan\ReviewController;
use App\Http\Controllers\DeviceScan\UploadController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::inertia('/dashboard', 'Dashboard')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Equipment
    |--------------------------------------------------------------------------
    */
    Route::prefix('library')
    ->name('library.')
    ->group(function () {

        Route::inertia('/', 'Library/Index')
            ->name('index');

        /*
        |--------------------------------------------------------------------------
        | Equipment
        |--------------------------------------------------------------------------
        */

        Route::inertia('/equipment/modules', 'Library/Equipment/Modules')
            ->name('equipment.modules');

        Route::inertia('/equipment/string-inverters', 'Library/Equipment/StringInverters')
            ->name('equipment.string-inverters');

        Route::inertia('/equipment/central-inverters', 'Library/Equipment/CentralInverters')
            ->name('equipment.central-inverters');

        /*
        |--------------------------------------------------------------------------
        | Import
        |--------------------------------------------------------------------------
        */

        Route::get('/import/module', [UploadController::class, 'create'])
            ->defaults('deviceType', 'module')
            ->name('import.module');

        Route::get('/import/string-inverter', [UploadController::class, 'create'])
            ->defaults('deviceType', 'string_inverter')
            ->name('import.string-inverter');

        Route::get('/import/central-inverter', [UploadController::class, 'create'])
            ->defaults('deviceType', 'central_inverter')
            ->name('import.central-inverter');

        Route::post('/import', [UploadController::class, 'store'])
            ->name('import.store');

        Route::get('/review/{deviceType}', [ReviewController::class, 'show'])
            ->name('review');

        Route::get('/preview', [UploadController::class, 'preview'])
            ->name('preview');

        Route::get('/preview-image', [UploadController::class, 'previewImage'])
            ->name('preview-image');

        /*
        |--------------------------------------------------------------------------
        | Export
        |--------------------------------------------------------------------------
        */

        Route::inertia('/export', 'Library/Export')
            ->name('export');

        /*
        |--------------------------------------------------------------------------
        | Compare
        |--------------------------------------------------------------------------
        */

        Route::inertia('/compare', 'Library/Compare')
            ->name('compare');
    });

    /*
    |--------------------------------------------------------------------------
    | DeviceScan compatibility routes
    |--------------------------------------------------------------------------
    | Keep these while controllers/views still reference device-scan.* names.
    */

    Route::get('/device-scan/upload', [UploadController::class, 'create'])
        ->defaults('deviceType', 'module')
        ->name('device-scan.upload');

    Route::post('/device-scan/upload', [UploadController::class, 'store'])
        ->name('device-scan.upload.store');

    Route::get('/device-scan/review/{deviceType}', [ReviewController::class, 'show'])
        ->name('device-scan.review');

    Route::get('/device-scan/preview', [UploadController::class, 'preview'])
        ->name('device-scan.preview');

    Route::get('/device-scan/preview-image', [UploadController::class, 'previewImage'])
        ->name('device-scan.preview-image');
});

require __DIR__.'/settings.php';
