<?php

declare(strict_types=1);

namespace App\Http\Controllers\DeviceScan;

use App\DeviceScan\Metadata\DeviceMetadata;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function show(string $deviceType = 'module'): Response
    {
        $upload = session('device_scan.last_upload');

        $datasheet = $upload['datasheet'] ?? null;

        return Inertia::render('Library/Review/Review', [
            'deviceType' => $deviceType,
            'deviceLabel' => DeviceMetadata::supportedDeviceTypes()[$deviceType] ?? $deviceType,
            'upload' => $upload,
            'datasheet' => $datasheet,
            'sourceDocument' => $upload['source_document'] ?? null,

            // Kept temporarily so old Vue sections do not explode while we update the page.
            'schema' => [],
            'values' => [],
            'extraction' => null,
            'metadata' => null,
            'tables' => data_get($datasheet, 'model_groups.0.tables', []),
            'matrices' => [],
        ]);
    }
}