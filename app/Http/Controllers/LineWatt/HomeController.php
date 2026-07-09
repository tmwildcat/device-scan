<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function __invoke(): Response
    {
        $manufacturerCounts = $this->centralManufacturerCounts(8);

        return Inertia::render('LineWatt/Home', [
            'libraryStats' => $this->publicLibraryStats(),
            'featuredManufacturers' => $manufacturerCounts ?: [
                ['manufacturer' => 'JA Solar', 'count' => null],
                ['manufacturer' => 'Jinko Solar', 'count' => null],
                ['manufacturer' => 'LONGi', 'count' => null],
                ['manufacturer' => 'Trina Solar', 'count' => null],
                ['manufacturer' => 'Huawei', 'count' => null],
                ['manufacturer' => 'Sungrow', 'count' => null],
            ],
            'featuredTechnologies' => $this->centralTechnologyList(8),
        ]);
    }

    /**
     * @return array{curated_datasheets:int,engineering_records:int,manufacturers:int,technologies:int,recently_updated:int}
     */
    private function publicLibraryStats(): array
    {
        if (! Schema::hasTable('compiled_device_records')) {
            return [
                'curated_datasheets' => 0,
                'engineering_records' => 0,
                'manufacturers' => 0,
                'technologies' => 0,
                'recently_updated' => 0,
            ];
        }

        $centralRecords = CompiledDeviceRecord::query()
            ->where('source_type', 'central_curated')
            ->whereIn('status', ['published', 'approved', 'compiled']);

        return [
            'curated_datasheets' => Schema::hasTable('device_datasheets')
                ? DeviceDatasheet::query()->where('source_type', 'central_curated')->count()
                : 0,
            'engineering_records' => (clone $centralRecords)->count(),
            'manufacturers' => (clone $centralRecords)->whereNotNull('manufacturer')->distinct('manufacturer')->count('manufacturer'),
            'technologies' => (clone $centralRecords)->whereNotNull('technology')->distinct('technology')->count('technology'),
            'recently_updated' => (clone $centralRecords)->where('updated_at', '>=', now()->subDays(30))->count(),
        ];
    }
}
