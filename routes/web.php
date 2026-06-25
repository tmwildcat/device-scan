<?php

use App\Http\Controllers\DeviceScan\ReviewController;
use App\Http\Controllers\DeviceScan\UploadController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    Route::get('/device-scan/upload', [UploadController::class, 'create'])
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