<?php

namespace Database\Seeders;

use App\Models\PowerSearchCategory;
use App\Models\PowerSearchOption;
use App\Models\CompiledDeviceRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PowerSearchTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $taxonomy = [
            'country-region' => [
                'name' => 'Country / Region',
                'options' => ['India', 'Europe', 'USA', 'China', 'Southeast Asia', 'Middle East', 'Africa', 'LATAM'],
            ],
            'country-programs' => [
                'name' => 'Country-specific Programs',
                'options' => ['PM-KUSUM Approved', 'ALMM Listed', 'DCR Eligible', 'Domestic Content Eligible', 'Local Content', 'CBAM-related'],
            ],
            'application' => [
                'name' => 'Application',
                'options' => ['Residential', 'Commercial', 'Industrial', 'Utility', 'Agricultural', 'Telecom', 'Data Center', 'Mini-grid'],
            ],
            'mounting-installation' => [
                'name' => 'Mounting / Installation',
                'options' => ['Rooftop', 'Ground mount', 'Floating', 'Carport', 'BIPV', 'Tracker', 'Fixed tilt'],
            ],
            'technology' => [
                'name' => 'Technology',
                'options' => ['TOPCon', 'HJT', 'PERC', 'IBC', 'CdTe', 'Bifacial', 'Double glass', '1500V Systems'],
            ],
            'commercial-bankability' => [
                'name' => 'Commercial / Bankability',
                'options' => ['Bloomberg Tier 1', 'Approved Vendor', 'Bankable', 'Warranty-backed', 'Partner Promoted'],
            ],
            'protection-inverter-features' => [
                'name' => 'Protection / Inverter Features',
                'options' => ['DC SPD', 'AC SPD', 'AFCI', 'RCMU', 'Anti-islanding', 'Grid monitoring', 'String monitoring'],
            ],
        ];

        $categoryIndex = 0;
        foreach ($taxonomy as $categorySlug => $category) {
            $categoryModel = PowerSearchCategory::query()->updateOrCreate(
                ['slug' => $categorySlug],
                [
                    'name' => $category['name'],
                    'scope' => 'all',
                    'sort_order' => $categoryIndex++,
                    'is_active' => true,
                ]
            );

            foreach ($category['options'] as $optionIndex => $label) {
                $slug = Str::slug($label);
                PowerSearchOption::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'power_search_category_id' => $categoryModel->id,
                        'label' => $label,
                        'scope' => $this->scopeFor($label),
                        'sort_order' => $optionIndex,
                        'is_active' => true,
                        'notes' => $this->notesFor($label),
                    ]
                );
            }
        }

        $this->assignSafeTechnologyTags();
    }

    private function scopeFor(string $label): string
    {
        return in_array($label, ['DC SPD', 'AC SPD', 'AFCI', 'RCMU', 'Anti-islanding', 'Grid monitoring', 'String monitoring'], true)
            ? 'inverter'
            : 'all';
    }

    private function notesFor(string $label): ?string
    {
        return in_array($label, ['PM-KUSUM Approved', 'ALMM Listed', 'DCR Eligible', 'Bloomberg Tier 1'], true)
            ? 'Curated/reference assignment only. Do not infer from datasheet text unless explicitly reviewed.'
            : null;
    }

    private function assignSafeTechnologyTags(): void
    {
        $technologyOptions = PowerSearchOption::query()
            ->whereIn('slug', ['topcon', 'hjt', 'perc', 'cdte'])
            ->get()
            ->keyBy('slug');

        if ($technologyOptions->isEmpty()) {
            return;
        }

        CompiledDeviceRecord::query()
            ->where('device_type', 'module')
            ->whereNotNull('technology')
            ->each(function (CompiledDeviceRecord $record) use ($technologyOptions): void {
                $technology = mb_strtolower((string) $record->technology);
                $matched = [];

                foreach ($technologyOptions as $slug => $option) {
                    if (str_contains($technology, $slug)) {
                        $matched[$option->id] = [
                            'source' => 'metadata',
                            'notes' => 'Assigned from existing reviewed metadata technology field.',
                            'assigned_at' => now(),
                        ];
                    }
                }

                if ($matched !== []) {
                    $record->powerSearchOptions()->syncWithoutDetaching($matched);
                }
            });
    }
}
