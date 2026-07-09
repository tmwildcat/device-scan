<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class BusinessAdminController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('LineWatt/BusinessAdmin', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'summary' => $this->summary(),
        ]);
    }

    public function placeholder(Request $request, string $section): Response
    {
        $title = str($section)->replace('-', ' ')->title()->toString();

        return Inertia::render('LineWatt/BusinessAdmin', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'summary' => $this->summary(),
            'placeholder' => [
                'title' => $title,
                'description' => match ($section) {
                    'discovery' => 'Discovery and SEO operations will track public library growth, manufacturer coverage, search journeys and acquisition signals.',
                    'compiler' => 'Compiler business oversight will summarize corpus coverage, extraction quality and manufacturer expansion priorities without exposing low-level system tools.',
                    default => 'Business operations workspace placeholder.',
                },
            ],
        ]);
    }

    /**
     * @return array<string,int>
     */
    private function summary(): array
    {
        return [
            'published_records' => Schema::hasTable('compiled_device_records')
                ? CompiledDeviceRecord::query()->where('source_type', 'central_curated')->where('status', 'published')->count()
                : 0,
            'pending_approval' => Schema::hasTable('compiled_device_records')
                ? CompiledDeviceRecord::query()
                    ->where(function ($query): void {
                        $query->whereIn('status', ['submitted_for_approval', 'librarian_review'])
                            ->orWhereIn('review_status', ['submitted', 'librarian_review']);
                    })
                    ->count()
                : 0,
            'datasheets' => Schema::hasTable('device_datasheets') ? DeviceDatasheet::query()->count() : 0,
            'manufacturers' => Schema::hasTable('manufacturer_companies') ? ManufacturerCompany::query()->count() : 0,
            'members' => Schema::hasTable('users') ? User::query()->where('role', LineWattRole::SUBSCRIBER)->count() : 0,
            'promotions' => Schema::hasTable('promotions') ? Promotion::query()->count() : 0,
        ];
    }
}
