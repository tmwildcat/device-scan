<?php

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

        return Inertia::render('DeviceScan/Review', [
            'deviceType' => $deviceType,
            'deviceLabel' => DeviceMetadata::supportedDeviceTypes()[$deviceType] ?? $deviceType,
            'schema' => DeviceMetadata::groupedFor($deviceType),
            'values' => $this->fakeValues($deviceType),
            'upload' => $upload,
        ]);
    }

    private function fakeValues(string $deviceType): array
    {
        return match ($deviceType) {
            'module' => [
                'make' => 'Jinko Solar',
                'model' => 'JKM605N-78HL4',
                'Technol' => 'N type Mono-crystalline',
                'PNom' => '605',
                'Vmp' => '45.49',
                'Imp' => '13.30',
                'Voc' => '55.10',
                'Isc' => '14.04',
                'modEff' => '21.64',
            ],
            'inverter' => [
                'make' => 'Sungrow',
                'model' => 'SG125CX-P2',
                'type' => 'string',
                'PnomAC' => '125',
                'VAbsMax' => '1100',
                'VmppMin' => '200',
                'VmppMax' => '1000',
                'NbMPPT' => '12',
            ],
            default => [],
        };
    }
}