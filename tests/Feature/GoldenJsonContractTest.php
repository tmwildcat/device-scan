<?php

use App\DeviceScan\Compilers\Inverters\DTO\InverterDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalModelDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalStcDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleSourceValueDto;
use App\DeviceScan\Golden\GoldenJsonBuilder;
use App\DeviceScan\Golden\GoldenJsonValidator;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('device-scan-test');
});

it('module records expose PAN export only', function () {
    $user = User::factory()->create(['role' => LineWattRole::SUBSCRIBER]);
    $record = goldenExportRecord('module');

    $this->actingAs($user)
        ->get(route('records.show', ['record' => $record->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/EngineeringRecordDetail')
            ->where('exportOptions.2.format', 'pan')
            ->where('exportOptions.3.format', 'summary-pdf')
        );

    $this->actingAs($user)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'pan']))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'ond']))
        ->assertNotFound();
});

it('inverter records expose OND export only', function () {
    $user = User::factory()->create(['role' => LineWattRole::SUBSCRIBER]);
    $record = goldenExportRecord('inverter');

    $this->actingAs($user)
        ->get(route('records.show', ['record' => $record->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/EngineeringRecordDetail')
            ->where('exportOptions.2.format', 'ond')
            ->where('exportOptions.3.format', 'summary-pdf')
        );

    $this->actingAs($user)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'ond']))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('records.export', ['record' => $record->uuid, 'format' => 'pan']))
        ->assertNotFound();
});

it('golden JSON validates against expected schema', function () {
    $builder = app(GoldenJsonBuilder::class);
    $validator = app(GoldenJsonValidator::class);

    $moduleGolden = $builder->moduleRecords(goldenModuleDto(), __FILE__)[0];
    $inverterGolden = $builder->inverterRecord(goldenInverterDto(), __FILE__);

    expect($validator->validate($moduleGolden))->toBe([])
        ->and($validator->validate($inverterGolden))->toBe([]);
});

it('golden JSON generation is repeatable for the same DTO and source', function () {
    $builder = app(GoldenJsonBuilder::class);
    $dto = goldenModuleDto();

    $first = $builder->moduleRecords($dto, __FILE__);
    $second = $builder->moduleRecords($dto, __FILE__);

    expect($second)->toEqual($first)
        ->and(json_encode($second, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->toBe(json_encode($first, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
});

it('one module datasheet can produce multiple golden model JSON records', function () {
    $records = app(GoldenJsonBuilder::class)->moduleRecords(goldenModuleDto(), __FILE__);

    expect($records)->toHaveCount(2)
        ->and($records[0]['identity']['power_class_w'])->toBe(600.0)
        ->and($records[1]['identity']['power_class_w'])->toBe(605.0)
        ->and($records[0]['engineering']['electrical_stc']['models'])->toHaveCount(1)
        ->and($records[1]['engineering']['electrical_stc']['models'])->toHaveCount(1);
});

function goldenModuleDto(): ModuleDto
{
    $source = fn (float $value, string $unit): ModuleSourceValueDto => new ModuleSourceValueDto(
        value: $value,
        unit: $unit,
        sourceText: "{$value} {$unit}",
        sourcePage: 2,
        sourceSection: 'Electrical Data STC',
        confidence: 0.92,
        normalizedValue: $value,
    );

    return new ModuleDto(
        manufacturer: 'Jinko Solar',
        series: 'Tiger Neo',
        family: 'JKM-N-78HL4',
        technology: 'TOPCon',
        models: ['JKM600N-78HL4', 'JKM605N-78HL4'],
        electricalStc: new ModuleElectricalStcDto([
            new ModuleElectricalModelDto(
                modelSeries: 'JKM595-615N-78HL4',
                modelVariants: ['JKM600N-78HL4', 'JKM600N-78HL4-V'],
                powerClassW: 600.0,
                displayName: 'JKM600N-78HL4',
                ratedMaxPowerW: $source(600, 'W'),
                openCircuitVoltageV: $source(52.0, 'V'),
                maximumPowerVoltageV: $source(43.5, 'V'),
                shortCircuitCurrentA: $source(14.5, 'A'),
                maximumPowerCurrentA: $source(13.8, 'A'),
                moduleEfficiencyPercent: $source(23.2, '%'),
            ),
            new ModuleElectricalModelDto(
                modelSeries: 'JKM595-615N-78HL4',
                modelVariants: ['JKM605N-78HL4', 'JKM605N-78HL4-V'],
                powerClassW: 605.0,
                displayName: 'JKM605N-78HL4',
                ratedMaxPowerW: $source(605, 'W'),
                openCircuitVoltageV: $source(52.2, 'V'),
                maximumPowerVoltageV: $source(43.7, 'V'),
                shortCircuitCurrentA: $source(14.6, 'A'),
                maximumPowerCurrentA: $source(13.85, 'A'),
                moduleEfficiencyPercent: $source(23.4, '%'),
            ),
        ]),
        sourceMetadata: ['fixture' => true],
    );
}

function goldenInverterDto(): InverterDto
{
    return new InverterDto(
        manufacturer: 'Sungrow',
        series: 'SG RT',
        modelSeries: 'SG5.0-12RT',
        modelName: 'SG10RT',
        powerClassKw: 10.0,
        displayName: 'Sungrow SG10RT',
        models: ['SG10RT'],
        deviceType: 'string_inverter',
        extractionQualityScore: 80,
        extractionQualityGrade: 'B',
        sourceMetadata: ['fixture' => true],
    );
}

function goldenExportRecord(string $deviceType): CompiledDeviceRecord
{
    $uuid = (string) Str::uuid();
    $datasheetPath = "uploads/{$uuid}.pdf";
    $compiledPath = "compiled/{$uuid}.json";
    $pdfContent = "%PDF-1.4\n% Golden export test\n";

    Storage::disk('device-scan-test')->put($datasheetPath, $pdfContent);
    Storage::disk('device-scan-test')->put($compiledPath, json_encode([
        'manufacturer' => $deviceType === 'module' ? 'Jinko Solar' : 'Sungrow',
        'display_name' => $deviceType === 'module' ? 'JKM600N-78HL4' : 'Sungrow SG10RT',
        'device_type' => $deviceType,
    ]));

    $datasheet = DeviceDatasheet::create([
        'source_type' => 'central_curated',
        'device_type' => $deviceType,
        'manufacturer' => $deviceType === 'module' ? 'Jinko Solar' : 'Sungrow',
        'product_name' => $deviceType === 'module' ? 'Tiger Neo' : 'SG RT',
        'status' => 'published',
        'review_status' => 'approved',
        'datasheet_disk' => 'device-scan-test',
        'datasheet_path' => $datasheetPath,
        'datasheet_original_filename' => 'datasheet.pdf',
        'datasheet_mime_type' => 'application/pdf',
        'datasheet_size_bytes' => strlen($pdfContent),
        'datasheet_sha256' => hash('sha256', $pdfContent),
        'pdf_access_mode' => 'internal_only',
        'permission_status' => 'unknown',
        'can_public_download' => false,
        'can_public_preview' => false,
        'can_internal_preview' => true,
        'can_private_download' => true,
    ]);

    return CompiledDeviceRecord::create([
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'central_curated',
        'device_type' => $deviceType,
        'manufacturer' => $datasheet->manufacturer,
        'series' => $datasheet->product_name,
        'model_series' => $deviceType === 'module' ? 'JKM595-615N-78HL4' : 'SG5.0-12RT',
        'model_name' => $deviceType === 'module' ? 'JKM600N-78HL4' : 'SG10RT',
        'display_name' => $deviceType === 'module' ? 'JKM600N-78HL4' : 'Sungrow SG10RT',
        'power_class_w' => $deviceType === 'module' ? 600 : null,
        'power_class_kw' => $deviceType === 'inverter' ? 10 : null,
        'status' => 'published',
        'review_status' => 'approved',
        'compiled_disk' => 'device-scan-test',
        'compiled_path' => $compiledPath,
        'compiled_sha256' => hash('sha256', (string) Storage::disk('device-scan-test')->get($compiledPath)),
    ]);
}
