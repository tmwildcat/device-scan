<?php

namespace App\LineWatt\Manufacturers;

use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ManufacturerNormalizer
{
    /**
     * @return array{name:?string,matched:bool,source:string,variants:list<string>}
     */
    public function normalize(?string $value): array
    {
        $input = trim((string) $value);

        if ($input === '') {
            return ['name' => null, 'matched' => false, 'source' => 'empty', 'variants' => []];
        }

        $key = $this->key($input);
        $aliases = $this->aliases();

        if (isset($aliases[$key])) {
            return ['name' => $aliases[$key], 'matched' => true, 'source' => 'alias', 'variants' => [$input]];
        }

        foreach ($this->knownManufacturers() as $manufacturer) {
            if ($this->key($manufacturer) === $key) {
                return ['name' => $manufacturer, 'matched' => true, 'source' => 'known', 'variants' => [$input]];
            }
        }

        return ['name' => $this->titleCaseManufacturer($input), 'matched' => false, 'source' => 'new', 'variants' => [$input]];
    }

    /**
     * @return array<int,array{label:string,value:string,matched:bool,source:string}>
     */
    public function suggestions(string $query, ?string $deviceType = null, int $limit = 20): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [];
        }

        $normalized = $this->normalize($query);
        $candidates = $this->knownManufacturers($deviceType)
            ->filter(fn (string $manufacturer): bool => str_contains($this->key($manufacturer), $this->key($query)))
            ->take($limit)
            ->map(fn (string $manufacturer): array => [
                'label' => $manufacturer,
                'value' => $manufacturer,
                'matched' => true,
                'source' => 'known',
            ]);

        if ($normalized['matched'] && $normalized['name']) {
            $candidates->prepend([
                'label' => $normalized['name'],
                'value' => $normalized['name'],
                'matched' => true,
                'source' => $normalized['source'],
            ]);
        }

        return $candidates
            ->unique('value')
            ->values()
            ->take($limit)
            ->all();
    }

    private function key(string $value): string
    {
        return Str::lower(preg_replace('/[^a-z0-9]+/i', '', $value) ?? '');
    }

    /**
     * @return Collection<int,string>
     */
    private function knownManufacturers(?string $deviceType = null): Collection
    {
        $names = collect();

        if (Schema::hasTable('manufacturer_companies')) {
            $names = $names->merge(ManufacturerCompany::query()->pluck('name'));
        }

        if (Schema::hasTable('compiled_device_records')) {
            $names = $names->merge(
                CompiledDeviceRecord::query()
                    ->when($deviceType, fn ($query) => $query->where('device_type', $deviceType))
                    ->whereNotNull('manufacturer')
                    ->where('manufacturer', '<>', '')
                    ->distinct()
                    ->pluck('manufacturer')
            );
        }

        if (Schema::hasTable('device_datasheets')) {
            $names = $names->merge(
                DeviceDatasheet::query()
                    ->when($deviceType, fn ($query) => $query->where('device_type', $deviceType))
                    ->whereNotNull('manufacturer')
                    ->where('manufacturer', '<>', '')
                    ->distinct()
                    ->pluck('manufacturer')
            );
        }

        return $names
            ->filter()
            ->map(fn (mixed $name): string => trim((string) $name))
            ->filter()
            ->unique(fn (string $name): string => $this->key($name))
            ->sort()
            ->values();
    }

    /**
     * @return array<string,string>
     */
    private function aliases(): array
    {
        return [
            'jasolar' => 'JA Solar',
            'jasolars' => 'JA Solar',
            'jinko' => 'Jinko Solar',
            'jinkosolar' => 'Jinko Solar',
            'longi' => 'LONGi',
            'longisolar' => 'LONGi',
            'trina' => 'Trina Solar',
            'trinasolar' => 'Trina Solar',
            'canadian' => 'Canadian Solar',
            'canadiansolar' => 'Canadian Solar',
            'firstsolar' => 'First Solar',
            'rec' => 'REC',
            'maxeon' => 'Maxeon',
            'sma' => 'SMA',
            'sungrow' => 'Sungrow',
            'huawei' => 'Huawei',
            'growatt' => 'Growatt',
            'fronius' => 'Fronius',
            'vikram' => 'Vikram Solar',
            'vikramsolar' => 'Vikram Solar',
            'adani' => 'Adani',
            'adanisolar' => 'Adani Solar',
            'astronergy' => 'Astronergy',
        ];
    }

    private function titleCaseManufacturer(string $value): string
    {
        $upper = ['JA', 'REC', 'SMA'];

        return collect(preg_split('/\s+/', trim($value)) ?: [])
            ->map(fn (string $part): string => in_array(Str::upper($part), $upper, true) ? Str::upper($part) : Str::headline($part))
            ->implode(' ');
    }
}
