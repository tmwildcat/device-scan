<?php

use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('device-scan-test');
    config([
        'device-scan.storage_disk' => 'device-scan-test',
        'device-scan.base_path' => 'device-scan',
        'linewatt-library.debug' => false,
    ]);
});

it('imports pasted PVSyst module data into a private engineering record', function () {
    $user = User::factory()->create(['role' => LineWattRole::SUBSCRIBER, 'plan_code' => 'pro']);

    $response = $this->actingAs($user)->post(route('my-library.pvsyst-import.store'), [
        'device_type' => 'module',
        'input_type' => 'paste',
        'mapping_template' => 'pvsyst_module_component',
        'manufacturer' => 'jinko',
        'model_name' => 'JKM600N-78HL4',
        'series' => 'Tiger Neo',
        'pvsyst_data' => '600,52.1,43.4,14.2,13.82,23.2,2465,1134,35,30.5,1500,30,-0.29,-0.25,0.046',
    ]);

    $record = CompiledDeviceRecord::query()->firstOrFail();
    $datasheet = DeviceDatasheet::query()->firstOrFail();

    $response->assertRedirect(route('my-library.records.review', ['record' => $record->uuid]));

    expect($datasheet->source_type)->toBe('pvsyst_import')
        ->and($datasheet->manufacturer)->toBe('Jinko Solar')
        ->and($datasheet->metadata['no_pdf_preview'])->toBeTrue()
        ->and($datasheet->can_internal_preview)->toBeFalse()
        ->and($datasheet->can_private_download)->toBeFalse()
        ->and($record->source_type)->toBe('pvsyst_import')
        ->and($record->device_type)->toBe('module')
        ->and((float) $record->power_class_w)->toBe(600.0);

    Storage::disk('device-scan-test')->assertExists($datasheet->datasheet_path);
    Storage::disk('device-scan-test')->assertExists($record->compiled_path);

    $payload = json_decode(Storage::disk('device-scan-test')->get($record->compiled_path), true);

    expect($payload['source_label'])->toBe('PVSyst Import')
        ->and($payload['electrical_stc']['models'][0]['rated_max_power_w']['normalized_value'])->toBe(600)
        ->and($payload['electrical_stc']['models'][0]['open_circuit_voltage_v']['normalized_value'])->toBe(52.1);
});

it('imports labelled PVSyst XLSX inverter data into a private engineering record', function () {
    $user = User::factory()->create(['role' => LineWattRole::SUBSCRIBER, 'plan_code' => 'pro']);
    $xlsx = pvsystXlsx([
        ['Rated AC Power', '110000'],
        ['Max DC Voltage', '1100'],
        ['Startup Voltage', '180'],
        ['Number of MPPT', '3'],
        ['DC SPD', 'Type II'],
        ['Rated AC Voltage', '400'],
        ['Frequency', '50/60'],
    ]);

    $this->actingAs($user)->post(route('my-library.pvsyst-import.store'), [
        'device_type' => 'inverter',
        'input_type' => 'xlsx',
        'mapping_template' => 'pvsyst_inverter_component',
        'manufacturer' => 'sungrow',
        'model_name' => 'SG110CX',
        'series' => 'CX',
        'pvsyst_file' => $xlsx,
    ])->assertRedirect();

    $record = CompiledDeviceRecord::query()->firstOrFail();
    $payload = json_decode(Storage::disk('device-scan-test')->get($record->compiled_path), true);

    expect($record->source_type)->toBe('pvsyst_import')
        ->and($record->device_type)->toBe('inverter')
        ->and((float) $record->power_class_kw)->toBe(110.0)
        ->and($payload['device_type'])->toBe('string_inverter')
        ->and($payload['dc_input']['models'][0]['fields']['max_dc_voltage']['normalized_value'])->toBe(1100);
});

it('requires a manufacturer for PVSyst imports', function () {
    $user = User::factory()->create(['role' => LineWattRole::SUBSCRIBER, 'plan_code' => 'pro']);

    $this->actingAs($user)
        ->from(route('my-library.uploads.new'))
        ->post(route('my-library.pvsyst-import.store'), [
            'device_type' => 'module',
            'input_type' => 'paste',
            'mapping_template' => 'pvsyst_module_component',
            'model_name' => 'PV-600',
            'pvsyst_data' => '600,52,43,14,13.8',
        ])
        ->assertRedirect(route('my-library.uploads.new'))
        ->assertSessionHasErrors('manufacturer');
});

it('does not expose PDF preview for PVSyst source records', function () {
    $user = User::factory()->create(['role' => LineWattRole::SUBSCRIBER, 'plan_code' => 'pro']);

    $this->actingAs($user)->post(route('my-library.pvsyst-import.store'), [
        'device_type' => 'module',
        'input_type' => 'paste',
        'mapping_template' => 'pvsyst_module_component',
        'manufacturer' => 'JA Solar',
        'model_name' => 'JAM72D40-600',
        'pvsyst_data' => '600,52,43,14,13.8,22',
    ]);

    $record = CompiledDeviceRecord::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('records.show', ['record' => $record->uuid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/EngineeringRecordDetail')
            ->where('pdfPolicy.can_embed', false)
            ->where('pdfPolicy.preview_url', null)
        );
});

it('keeps PAN and OND export rules by imported device type', function () {
    $user = User::factory()->create(['role' => LineWattRole::SUBSCRIBER, 'plan_code' => 'pro']);

    $this->actingAs($user)->post(route('my-library.pvsyst-import.store'), [
        'device_type' => 'module',
        'input_type' => 'paste',
        'mapping_template' => 'pvsyst_module_component',
        'manufacturer' => 'Trina Solar',
        'model_name' => 'TSM-NE19R 600W',
        'pvsyst_data' => '600,52,43,14,13.8,22',
    ]);

    $module = CompiledDeviceRecord::query()->firstOrFail();

    $this->actingAs($user)->get(route('records.export', ['record' => $module->uuid, 'format' => 'pan']))->assertOk();
    $this->actingAs($user)->get(route('records.export', ['record' => $module->uuid, 'format' => 'ond']))->assertNotFound();

    $this->actingAs($user)->post(route('my-library.pvsyst-import.store'), [
        'device_type' => 'inverter',
        'input_type' => 'paste',
        'mapping_template' => 'pvsyst_inverter_component',
        'manufacturer' => 'Sungrow',
        'model_name' => 'SG110CX',
        'pvsyst_data' => '110000,121000,110000,1100,180,600,200-1000,3,2,30,45,400,50,158,175,1,3',
    ]);

    $inverter = CompiledDeviceRecord::query()->where('device_type', 'inverter')->firstOrFail();

    $this->actingAs($user)->get(route('records.export', ['record' => $inverter->uuid, 'format' => 'ond']))->assertOk();
    $this->actingAs($user)->get(route('records.export', ['record' => $inverter->uuid, 'format' => 'pan']))->assertNotFound();
});

function pvsystXlsx(array $rows): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'pvsyst').'.xlsx';
    $strings = collect($rows)->flatten()->values()->all();
    $sharedStrings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($strings).'" uniqueCount="'.count($strings).'">'
        .collect($strings)->map(fn (string $value): string => '<si><t>'.htmlspecialchars($value, ENT_XML1).'</t></si>')->implode('')
        .'</sst>';
    $sheetRows = collect($rows)->map(function (array $row, int $rowIndex): string {
        $rowNumber = $rowIndex + 1;
        $cells = collect($row)->map(function (string $value, int $cellIndex) use ($rowNumber): string {
            $column = chr(65 + $cellIndex);
            $sharedIndex = ($rowNumber - 1) * 2 + $cellIndex;

            return '<c r="'.$column.$rowNumber.'" t="s"><v>'.$sharedIndex.'</v></c>';
        })->implode('');

        return '<row r="'.$rowNumber.'">'.$cells.'</row>';
    })->implode('');
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>';

    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('xl/sharedStrings.xml', $sharedStrings);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->close();

    return new UploadedFile($path, 'components.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}
